**English** | [Español](README-es.md) | [Português Brasileiro](README-pt_BR.md)

# Cypress tests

The tests use disposable OMP 3.4 and Thoth instances, without changing your local
installation or writing to public Thoth APIs.

## Requirements

Docker Compose v2, Python 3.9+, Linux/amd64 (or compatible emulation), and an OMP
`stable-3_4_0` MySQL dataset for context `publicknowledge`, containing `database.sql`,
`files/` and `public/` from the same snapshot.

## Prepare and run

```sh
python3 tests/environment/environment.py prepare --dataset /path/to/omp-dataset --apply
python3 tests/environment/environment.py run --apply
```

`prepare` builds images, starts or resumes all services, renews credentials when
needed, and restores the disposable OMP dataset. It replaces OMP test data without
changing the source dataset. Thoth publisher and imprint are preserved.

`run` executes the suite once; add `--spec ThothRegistration.cy.js` for one spec.
After preparation, use `open --apply` for the GUI. This requires X11/XWayland and
`xauth` in the same graphical session. The container accesses your X11 session;
use trusted images and tests. Spec edits are mounted without rebuilding.
Close Cypress before another environment command; closing it leaves services running.

`open` and `run` check authenticated OMP-to-Thoth connectivity without the plugin
cache. They reuse data and never restore it implicitly. On failure, run `prepare`
again. After reboot, `prepare` resumes Thoth and restores OMP.

Without `--apply`, mutation commands only show a plan. `status` reports containers
without changing them. `down --apply` removes this project's disposable services,
volumes and credentials. Run `prepare` again to start fresh.

## Credentials and CI

Scoped test tokens last two days. Preparation and API startup renew expired,
revoked or nearly expired tokens (less than one hour remaining). The API identity
and long-lived administrative PAT stay in the private `bootstrap` volume. OMP
receives only the read-only `client` volume. `down` removes both.

In CI, `/builds` is shared with the job: administrative files remain accessible to
the job until final cleanup. Private-volume separation applies to local Compose.
CI uses the same internal `prepare` and `run` commands, running twice after one preparation.

Old environments require `down --apply` once before the new `prepare`, because they
did not persist the API key. The old `up`, `cypress` and `smoke` commands were removed;
use `prepare`, `run` and `status`.

## Registration coverage

`ThothRegistration.cy.js` seeds a complete published book in OMP, registers it through
the UI and checks the persisted Thoth metadata with an independent GraphQL query.
The fixture covers bilingual titles, abstracts and biographies, DOI, date, edition,
place, page/image counts, license, copyright holder, cover URL, contributor identity,
ORCID, website, language, subjects (BISAC, BIC, THEMA, LCC and
keywords), references, PDF/EPUB/paperback formats, ISBN, accessibility and digital
locations, plus a chapter with its own DOI, pages, translated metadata and contribution.

The full fixture is exclusive to this scenario; other tests keep their smaller fixtures.
Accessibility conformance and exemption are exercised on separate digital formats,
as required by Thoth. Cover hosting/upload to S3 and chapter-specific files/locations
are outside this scenario. It covers metadata families, not every combination of values.

OMP 3.4 stores subjects as strings: the fixture uses classification prefixes.
Structured ROR affiliations are not available in this core; the test checks that no
affiliation is invented. The publication workflow refreshes Thoth status after a page reload.
For simultaneous versions, give each new environment a different `--port` on first preparation.
