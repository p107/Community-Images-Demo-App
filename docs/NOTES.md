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

### Frontend

- Mamy tu sporo wymieszanych rzeczy, ale minimalną zmianą dającą "lepszy frontend" mogłoby być wyodrębnienie css, zapisanie ich jkako scss i budowanie chociażby webpackiem lub gulpem.
- Brak tu RWD. Aplikacja to _biedny klon Instagrama_ ale nie ma nawet podstawowej responsywności. Dodanie RWD to byłaby duża poprawa UX.

## Rozszerzone funkcjonalności

### Phoenix API i endpoint z pełnymi danymi zdjęcia

Rozszerzenie Phoenix API o endpoint z pełnymi danymi zdjęcia (location, camera, description, taken_at).

Można też było uzupełnić istniejący endpoint o brakujące dane co przy małych ilościach danych jest jak najbardziej ok. Natomiast dodanie endpointu z pełnymi danymi dla pojedynczego zdjęcia pozwala na bardziej elastyczne zarządzanie danymi i lepszą separację odpowiedzialności. Endpoint z pełnymi danymi może być wykorzystywany nie tylko do importu, ale także do innych funkcjonalności, np. wyświetlania szczegółów zdjęcia w SymfonyApp bez konieczności importowania go.

Należy też pamiętać, że to tylko demostracyjna aplikacja i w realnym projekcie to jest moment gdy należy dodać kolejkowanie dla importu aby uniknąć timeoutów, błędów, umożliwić ponawianie itp. W minimalnym rozwiązaniu to mógłby być Symfony Messanger z transportem na zwykłej tabeli w bazie, w bardziej rozbudowanej formie z transportem na bazie no-sql np Mongu lub w systemie kolejkowym jak RabbitMQ. W przypadku dużej ilości danych i długotrwałych operacji to jest praktycznie konieczne aby zapewnić stabilność i skalowalność rozwiązania.

### Reakcja na limity

Po stronie aplikacji wprowadzono reakcję na limity API Phoenix. W przypadku przekroczenia limitów jest wyświetlany komunikat informujący użytkownika o konieczności odczekania przed ponowną próbą importu. 
