FROM ghcr.io/thoth-pub/thoth@sha256:e18bbedee479f0e18334019ba9b7ea9be53b1807ddb37c8fbb04a9273f8e9168 AS thoth
FROM ghcr.io/zitadel/zitadel:v3.2.2@sha256:6d3b73f80710abd4abf5344d902ad6b8cc653344a2c1c54fa01ef3fc5a22c2b0 AS zitadel
FROM python:3.13-alpine@sha256:7415fbc3c9e4979cc717d92377ab2bc7b2b4a2af1ac03cc52b5f3f88efedaf3a
COPY --from=thoth /thoth /usr/local/bin/thoth
COPY --from=thoth /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=zitadel /app /app
COPY common.py server.py smoke.py zitadel.py /environment/
EXPOSE 8000 8080
ENV PYTHONUNBUFFERED=1 PYTHONDONTWRITEBYTECODE=1
ENTRYPOINT ["python3", "/environment/server.py"]
