"""Focused checks for safe planning and error handling, without Docker."""
import datetime
import json
import pathlib
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch
from urllib.error import HTTPError

from common import graphql, request
import environment
import server
from environment import execute_cypress


class EnvironmentTests(unittest.TestCase):
    def test_prepare_starts_thoth_before_restoring_omp_on_first_use(self):
        with tempfile.TemporaryDirectory() as temp:
            root = pathlib.Path(temp)
            for directory in ['files', 'public']:
                (root / directory).mkdir()
            (root / 'database.sql').touch()
            calls = []

            def command(args, **kwargs):
                calls.append(args)
                return subprocess.CompletedProcess(args, 0, stdout='container\n')

            with patch.object(environment, 'ROOT', root), patch.object(environment, 'STATE', root / '.state'), \
                    patch('sys.argv', ['environment.py', 'prepare', '--dataset', str(root), '--apply']), \
                    patch('environment.subprocess.run', side_effect=command):
                environment.main()
            starts = [call for call in calls if 'up' in call]
            self.assertTrue(any('gateway' in call for call in starts))
            self.assertLess(next(i for i, call in enumerate(starts) if 'gateway' in call),
                            next(i for i, call in enumerate(starts) if 'cypress' in call))

    def test_unavailable_thoth_prevents_headless_and_graphical_launch(self):
        for action in ['open', 'run']:
            with self.subTest(action=action), tempfile.TemporaryDirectory() as temp:
                state = pathlib.Path(temp)
                (state / 'runtime.json').write_text(json.dumps({'OMP_DATASET': temp, 'version': 2}))
                (state / 'interactive.json').write_text('{}')
                (state / 'client.json').write_text('{}')
                calls = []

                def command(args, **kwargs):
                    calls.append(args)
                    if any('cypress-check.php' in str(arg) for arg in args):
                        raise subprocess.CalledProcessError(1, args)
                    return subprocess.CompletedProcess(args, 0, stdout='container\n')

                with patch.object(environment, 'STATE', state), \
                        patch('sys.argv', ['environment.py', action, '--apply']), \
                        patch('environment.subprocess.run', side_effect=command), \
                        patch('environment.open_cypress') as gui:
                    with self.assertRaisesRegex(RuntimeError, 'prepare'):
                        environment.main()
                    gui.assert_not_called()
                self.assertFalse(any('setsid' in call for call in calls))

    def test_restart_reuses_identity_instead_of_bootstrapping_again(self):
        with tempfile.TemporaryDirectory() as temp:
            state = pathlib.Path(temp)
            identity = {'privateKey': 'test-key', 'userId': 'user', 'orgId': 'org'}
            (state / 'bootstrap.json').write_text(json.dumps(identity))
            (state / 'admin.pat').write_text('test-admin')
            (state / 'ready').touch()
            with patch.object(server, 'STATE', state), patch('server.request') as request, \
                    patch('server.subprocess.run') as setup:
                self.assertEqual(server.bootstrap(), identity)
                request.assert_not_called()
                setup.assert_not_called()

    def test_expired_token_is_replaced_without_recreating_publisher(self):
        with tempfile.TemporaryDirectory() as temp:
            state = pathlib.Path(temp)
            client = state / 'client'
            client.mkdir()
            fixture = {'publisherId': 'publisher', 'imprintId': 'imprint'}
            (state / 'bootstrap.json').write_text(json.dumps({'userId': 'user'}))
            (state / 'fixture.json').write_text(json.dumps(fixture))
            (state / 'admin.pat').write_text('admin')
            (client / 'client.json').write_text(json.dumps(dict(fixture, token='old', tokenId='old-id',
                                                               expiresAt='2000-01-01T00:00:00+00:00')))
            with patch.object(server, 'STATE', state), patch.object(server, 'CLIENT', client), \
                    patch.dict('os.environ', {'THOTH_GRAPHQL_API': 'http://api:8000'}), \
                    patch('server.graphql', return_value={'me': {'userId': 'user'}}) as graphql, \
                    patch('server.request', return_value={'token': 'new', 'tokenId': 'new-id'}) as request:
                server.refresh_credentials()
            saved = json.loads((client / 'client.json').read_text())
            self.assertEqual(saved['token'], 'new')
            self.assertEqual(saved['publisherId'], fixture['publisherId'])
            self.assertEqual(saved['imprintId'], fixture['imprintId'])
            self.assertEqual((client / 'client.json').stat().st_mode & 0o777, 0o600)
            self.assertGreater(datetime.datetime.fromisoformat(saved['expiresAt']),
                               datetime.datetime.now(datetime.timezone.utc))
            self.assertFalse(any('mutation' in call.args[2] for call in graphql.call_args_list))
            self.assertEqual(request.call_args.kwargs, {'method': 'DELETE'})
            self.assertTrue(request.call_args.args[0].endswith('/old-id'))

    def test_valid_credentials_are_reused_without_rotation(self):
        with tempfile.TemporaryDirectory() as temp:
            state = pathlib.Path(temp)
            (state / 'bootstrap.json').write_text(json.dumps({'userId': 'user'}))
            (state / 'fixture.json').write_text(json.dumps({'publisherId': 'publisher', 'imprintId': 'imprint'}))
            (state / 'admin.pat').write_text('admin')
            credential = {'token': 'valid', 'expiresAt': '2099-01-01T00:00:00+00:00'}
            (state / 'client.json').write_text(json.dumps(credential))
            with patch.object(server, 'STATE', state), patch.object(server, 'CLIENT', state), \
                    patch('server.graphql', return_value={'me': {'userId': 'user'}}), \
                    patch('server.request') as request:
                server.refresh_credentials()
                request.assert_not_called()
            self.assertEqual(json.loads((state / 'client.json').read_text()), credential)

    def test_removed_commands_are_rejected(self):
        for action in ['up', 'cypress', 'smoke']:
            result = subprocess.run([sys.executable, environment.__file__, action],
                                    capture_output=True, text=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn('invalid choice', result.stderr)

    def test_plan_does_not_create_state_or_call_docker(self):
        with tempfile.TemporaryDirectory() as temp:
            script = pathlib.Path(temp) / 'environment.py'
            shutil.copyfile(pathlib.Path(__file__).with_name('environment.py'), script)
            for action in ['down', 'open', 'run']:
                result = subprocess.run([sys.executable, str(script), action],
                                        env={'PATH': ''}, capture_output=True, text=True)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertIn('Plan:', result.stdout)
            self.assertFalse((pathlib.Path(temp) / '.state').exists())

    def test_prepare_plan_requires_dataset_without_creating_state(self):
        with tempfile.TemporaryDirectory() as temp:
            root = pathlib.Path(temp)
            script = root / 'environment.py'
            shutil.copyfile(pathlib.Path(__file__).with_name('environment.py'), script)
            command = [sys.executable, str(script), 'prepare', '--dataset', str(root)]
            result = subprocess.run(command, env={'PATH': ''}, capture_output=True, text=True)
            self.assertNotEqual(result.returncode, 0)
            (root / 'database.sql').touch()
            (root / 'files').mkdir()
            (root / 'public').mkdir()
            result = subprocess.run(command, env={'PATH': ''}, capture_output=True, text=True)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertIn('omp-db/thoth_cypress only', result.stdout)
            self.assertFalse((root / '.state').exists())

    def test_run_rejects_spec_outside_plugin_before_mutation(self):
        with tempfile.TemporaryDirectory() as temp:
            script = pathlib.Path(temp) / 'environment.py'
            shutil.copyfile(pathlib.Path(__file__).with_name('environment.py'), script)
            for spec in ['../other.spec.js', '/tmp/other.spec.js', 'missing.spec.js']:
                result = subprocess.run([sys.executable, str(script), 'run', '--spec', spec, '--apply'],
                                        env={'PATH': ''}, capture_output=True, text=True)
                self.assertNotEqual(result.returncode, 0)
                self.assertIn('--spec must name', result.stderr)
            self.assertFalse((pathlib.Path(temp) / '.state').exists())

    def test_spec_is_only_accepted_for_run(self):
        result = subprocess.run([sys.executable, str(pathlib.Path(__file__).with_name('environment.py')),
                                 'status', '--spec', 'ThothRegistration.spec.js'], capture_output=True, text=True)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('--spec is only supported by run', result.stderr)

    def test_interrupted_cypress_still_requests_remote_process_cleanup(self):
        calls = []

        def docker(*args):
            calls.append(args)
            if 'setsid' in args:
                raise KeyboardInterrupt()

        with self.assertRaises(KeyboardInterrupt):
            execute_cypress(docker, 'entrypoint.sh', '--open', display=':0', authority='/root/.Xauthority')
        self.assertEqual(len(calls), 2)
        self.assertIn('setsid', calls[0])
        self.assertIn('python3', calls[1])
        self.assertIn('os.killpg', calls[1][-2])
        self.assertIn(calls[1][-1], calls[0])

    def test_graphical_launch_preserves_x11_access_without_affecting_headless(self):
        for display in (':0', None):
            calls = []
            execute_cypress(lambda *args: calls.append(args), 'entrypoint.sh',
                            '--open' if display else '--run',
                            display=display, authority='/root/.Xauthority' if display else None)
            option = 'XAUTHORITY=/root/.Xauthority'
            if display:
                self.assertIn(option, calls[0])
            else:
                self.assertNotIn(option, calls[0])

    def test_invalid_port_is_rejected_before_mutation(self):
        result = subprocess.run([sys.executable, str(pathlib.Path(__file__).with_name('environment.py')),
                                 'down', '--apply', '--port', '80'], capture_output=True, text=True)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('--port must be', result.stderr)

    def test_http_error_reports_status_without_response_secrets(self):
        error = HTTPError('http://zitadel:8080/path', 403, 'secret-response', {}, None)
        with patch('urllib.request.urlopen', side_effect=error):
            with self.assertRaisesRegex(RuntimeError, 'HTTP 403 from GET zitadel:8080/path') as raised:
                request('http://zitadel:8080/path', 'secret-token')
        self.assertNotIn('secret', str(raised.exception))

    def test_graphql_errors_are_not_mistaken_for_success(self):
        with patch('common.request', return_value={'data': {'createWork': None}, 'errors': [{'message': 'secret'}]}):
            with self.assertRaisesRegex(RuntimeError, 'GraphQL operation failed'):
                graphql('http://api:8000', 'token', '{ me { userId } }')


if __name__ == '__main__':
    unittest.main()
