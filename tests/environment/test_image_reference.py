"""The CI image reference must identify the manifest produced by this build."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest


EXPORTER = Path(__file__).with_name('export-image-reference.sh')
REPOSITORY = 'registry.example.test/group/project/test-environment'
DIGEST = 'sha256:' + 'a' * 64


class ImageReferenceTests(unittest.TestCase):
    def export(self, metadata):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            source = root / 'metadata.json'
            output = root / 'build.env'
            source.write_text(json.dumps(metadata, indent=2))
            result = subprocess.run(
                ['sh', str(EXPORTER), str(source), REPOSITORY, str(output)],
                capture_output=True, text=True,
                env=dict(os.environ, THOTH_ENVIRONMENT_IMAGE='attacker.example/image:mutable'),
            )
            return result, output.read_text() if output.exists() else None

    def test_exports_manifest_digest_instead_of_config_digest_or_environment(self):
        result, reference = self.export({
            'containerimage.config.digest': 'sha256:' + 'b' * 64,
            'containerimage.digest': DIGEST,
        })
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(reference, f'THOTH_ENVIRONMENT_IMAGE={REPOSITORY}@{DIGEST}\n')

    def test_missing_or_invalid_manifest_digest_produces_no_reference(self):
        for digest in [None, '', 'latest', 'sha256:' + 'a' * 63,
                       'sha256:' + 'a' * 65, 'sha256:' + 'g' * 64,
                       DIGEST + '\nOTHER_IMAGE=attacker', 'sha256:' + 'A' * 64]:
            with self.subTest(digest=digest):
                metadata = {'containerimage.config.digest': DIGEST}
                if digest is not None:
                    metadata['containerimage.digest'] = digest
                result, reference = self.export(metadata)
                self.assertNotEqual(result.returncode, 0)
                self.assertIsNone(reference)

    def test_unreadable_metadata_fails_without_writing_reference(self):
        with tempfile.TemporaryDirectory() as temporary:
            output = Path(temporary) / 'build.env'
            result = subprocess.run(
                ['sh', str(EXPORTER), str(output.with_suffix('.json')), REPOSITORY, str(output)],
                capture_output=True, text=True,
            )
            self.assertNotEqual(result.returncode, 0)
            self.assertFalse(output.exists())


if __name__ == '__main__':
    unittest.main()
