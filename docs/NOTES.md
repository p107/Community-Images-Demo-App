# Notes

## Znalezione błędy

- katalogi (namespac): entity, repository, service - wymieszane w jednym folderze, coś jak namiastka DDD albo podejścia bundlowego - ze względu na brak wytycznych w tym zakresie, zmieniam to
- brak wstrzykiwania zależności: np w HomeController
- return w HomeController:: index nie jest typu JsonResponse
- problem z uprawnienia, poprawka d Dockerfile (Error response from daemon: failed to create task for container: failed to create shim task: OCI runtime create failed: runc create failed: unable to start container process: error during container init: exec: "/entrypoint.sh": permission denied: unknown)
- 