# Notes

## Znalezione błędy / poprawki

- Katalogi (namespac): entity, repository, service - wymieszane w jednym folderze, coś jak namiastka DDD albo podejścia bundlowego - ze względu na brak wytycznych w tym zakresie, zmieniam to
- Brak wstrzykiwania zależności w HomeController
- HomeController index nie jest typu JsonResponse
- Problem z uprawnienia, poprawka w Dockerfile (Error response from daemon: failed to create task for container: failed to create shim task: OCI runtime create failed: runc create failed: unable to start container process: error during container init: exec: "/entrypoint.sh": permission denied: unknown)
- AuthController - SQL Injection, brak powiązania tokenu z użytkownikiem, brak obsługi błędów
- PhotoController - wstrzykiwanie zależności
- Wyodrębnienie logiki pobierania zalogowanego użytkownika (home i profile)
- Usunięcie `setUser()` z `LikeRepository` - stan użytkownika w repozytorium narusza zasadę pojedyńczej odpowiedzialności i utrudnia testowanie. Przekazanie `User` jako argument.
- Dodanie interfejsu dla `PhotoRepository` - zasada open/closed, brak interfejsu → niemożliwe mockowanie w testach
- Poprawa obsługi wyjątków w `LikeService`. Dedykowany `LikeException`.
 

## Warto by było jeszcze:

- Autentykacja methodą POST bo jeśli ktoś użyje aktualnego linka do autentykacji w przeglądarce (nie incognito) to token będzie widoczny w historii przeglądarki, a to nie jest bezpieczne
- ProfileController powinien zostać zrefaktorowany bo jest tam trochę akcji związanej z external API, a trochę z profilem użytkownika - można by to rozdzielić


## Rozszerzone funkcjonalności

- Rozszerzenie Phoenix API o endpoint z pełnymi danymi zdjęcia (location, camera, description, taken_at)
- 
- 