"""Exercise authenticated CRUD and isolation against the actual Thoth API."""
import json
import sys
import uuid
from common import graphql


def verify(url, credentials):
    token = credentials["token"]
    me = graphql(url, token, '''{ me { isSuperuser publisherContexts {
        publisher { publisherId imprints { imprintId } }
        permissions { workLifecycle }
    } } }''')["me"]
    assert not me["isSuperuser"], "Test account must not be a superuser"
    contexts = me["publisherContexts"]
    assert len(contexts) == 1
    assert contexts[0]["publisher"]["publisherId"] == credentials["publisherId"]
    assert contexts[0]["permissions"]["workLifecycle"]
    marker = "Cypress probe " + str(uuid.uuid4())
    data = {"workType": "MONOGRAPH", "workStatus": "FORTHCOMING",
            "imprintId": credentials["imprintId"], "reference": marker, "edition": 1}
    create = 'mutation($data: NewWork!) { createWork(data: $data) { workId reference } }'
    # An anonymous mutation must be rejected, not merely hidden by the UI.
    try:
        graphql(url, None, create, {"data": data})
    except RuntimeError:
        pass
    else:
        raise AssertionError("Anonymous write was accepted")
    work = graphql(url, token, create, {"data": data})["createWork"]
    work_id = work["workId"]
    try:
        assert work["reference"] == marker
        query = 'query($id: Uuid!) { work(workId: $id) { workId reference workStatus } }'
        assert graphql(url, token, query, {"id": work_id})["work"]["reference"] == marker
        data.update(workId=work_id, reference=marker + " updated")
        updated = graphql(url, token,
            'mutation($data: PatchWork!) { updateWork(data: $data) { reference } }',
            {"data": data})["updateWork"]
        assert updated["reference"] == marker + " updated"
        assert graphql(url, token, query, {"id": work_id})["work"]["reference"] == marker + " updated"
    finally:
        graphql(url, token, 'mutation($id: Uuid!) { deleteWork(workId: $id) { workId } }', {"id": work_id})
    print("PASS: scoped authentication; anonymous write rejected; create/read/update/delete")


if __name__ == "__main__":
    with open(sys.argv[1]) as source:
        credentials = json.load(source)
    verify(sys.argv[2] if len(sys.argv) > 2 else credentials["url"], credentials)
