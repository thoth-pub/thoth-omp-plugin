# Architecture

This branch targets OMP 3.5 and PHP 8.2. The plugin keeps its existing layers:

- Hooks, listeners, handlers and endpoints adapt OMP input and present results.
- Services coordinate registration, synchronization and file workflows.
- Factories transform supplied metadata into Thoth inputs.
- Repositories encapsulate GraphQL operations and interpret remote lookup failures.
- `OmpMetadataSource` loads the OMP data and URLs needed by book, chapter and location factories.

## Dependency construction

`ThothContainer` and its providers construct dependencies separately for each context. Configuration,
client and permission repository use that explicit context ID. Existing facades remain available at
OMP presentation boundaries; application services do not resolve collaborators through them.

Endpoint and listener constructors receive service factories where resolution must be deferred.
Registering routes or publishing without Thoth opt-in must not load credentials or initialize the
Thoth client. These closures are supplied by the provider, not obtained from a global container by
the endpoint or listener.

## Registration

`ThothBookRegistrationService::register()` creates the book, registers its metadata, activates it
when appropriate, and persists the submission's `thothWorkId`. Both manual registration and the
publication listener invoke this operation. The chosen work type is an explicit argument.

The result contains only the remote work ID and warning translation keys. Notifications are outside
this operation. The local repository edit runs in a database transaction. After an exception following
remote creation, the service restores the publication's temporary book ID and attempts to remove the
remote book. If compensation also fails, `ThothRegistrationException` retains the remote ID, the
original failure and the compensation failure.

OMP and Thoth do not share an atomic transaction. Remote deletion can fail, especially after
activation, and related remote resources are subject to Thoth's deletion rules. This implementation
reports incomplete compensation; it does not promise rollback of every remote side effect, automatic
retry or protection against concurrent registration requests.

## Metadata and results

`ThothBookFactory`, `ThothChapterFactory` and `ThothLocationFactory` receive explicit metadata. They
do not query repositories, resolve URLs or inspect the current HTTP request. `OmpMetadataSource`
is the OMP-specific boundary for that preparation and rejects missing required entities.

The top-level synchronization method, `ThothBookService::update()` and each
`synchronizeByPublication()` return `list<string>` containing warning translation keys. An empty
list means no warnings; failures are exceptions. The coordinator preserves execution order and
removes duplicate warnings. Lower-level reconciliation methods may still return a boolean describing
a specific outcome, such as skipped deletion.

Work lookup distinguishes confirmed absence from API failure. Only the documented missing-record
response is translated to `null`; authorization, transport and other query errors propagate. This
translation lives in the work repositories, including lookups by DOI.

## Validation

Tests cover lookup failures, registration stages and failed compensation, explicit metadata mapping,
context isolation, warning aggregation, listener delegation and route registration through the real
OMP hook dispatcher. Existing reconciliation tests remain in place.

Run the plugin's PHPUnit suite through the OMP 3.5 runner described in the README. JavaScript tests
can be run with `node --test plugins/generic/thoth/tests/js/*.test.mjs` from the OMP root.

The corresponding OMP 3.3 and 3.4 branches require their own adaptations and validation; the PHP 8.2
syntax and OMP integration in this branch must not be copied to them mechanically.
