# Notes

## Znalezione błędy i poprawki

### 1. Katalog _Like_
Entity, repository, service były wymieszane w jednym folderze. Mogło to być podejście jak Bundle lub namiastka DDD, ale brakowało jasnej struktury. Zorganizowano w podkatalogi: `Entity/`, `Repository/`, `Service/` — co poprawia czytelność i skalowalność oraz jest w zgodzie z typowymi projektami Symfony.

### 2. SQL Injection + Broken Authentication (`AuthController`)
Surowe zapytania SQL z interpolacją zmiennych (`'$token'`, `'$username'`) były podatne na SQL Injection. Dodatkowo token i użytkownik byli weryfikowani **niezależnymi** zapytaniami — encja `AuthToken` miała już relację `ManyToOne → User`, ale kontroler jej nie używał. W efekcie dowolnym istniejącym tokenem można było zalogować się na dowolnego użytkownika. Zastąpiono surowy SQL zapytaniem przez `AuthTokenRepository` (Doctrine ORM).

### 3. Brak wstrzykiwania zależności (np w `PhotoController`)
`LikeRepository` i `LikeService` były tworzone ręcznie przez `new` wewnątrz kontrolera. Zamieniono na wstrzykiwanie przez konstruktor z kontenera Symfony.

### 4. Powtarzający się kod pobierania zalogowanego użytkownika
Blok `$session->get('user_id') + $em->find(User)` był kopiowany w `HomeController` i `ProfileController`. Wyodrębniono do serwisu `CurrentUserProvider`.

### 5. Stan użytkownika w `LikeRepository` (naruszenie SRP)
`setUser()` w repozytorium naruszało zasadę pojedynczej odpowiedzialności — repozytorium trzymało stan zamiast bezstanowo operować na danych. Usunięto setter i przekazano `User` jako jawny argument metod: `hasUserLikedPhoto(Photo, User)`, `createLike(Photo, User)`, `unlikePhoto(Photo, User)`. Zaktualizowano `LikeRepositoryInterface` i wszystkich wywołujących.

### 6. Brak interfejsu dla `PhotoRepository` (naruszenie OCP)
`PhotoRepository` nie implementował interfejsu, co uniemożliwiało mockowanie w testach. Dodano `PhotoRepositoryInterface` w `src/Shared/`.

### 7. Ogólna obsługa wyjątków w `LikeService`
Łapanie `\Throwable` i rzucanie ogólnego `\Exception` ukrywało błędy i traciło oryginalny stack trace. Stworzono dedykowany `LikeException` z zachowaniem oryginalnego wyjątku jako `$previous`.

---

## Co warto by jeszcze poprawić

- **Autentykacja metodą POST** — aktualny link `/auth/{username}/{token}` naraża token na wyciek przez historię przeglądarki, logi serwera i nagłówek `Referer`. Bezpieczniejsze byłoby przesyłanie tokenu w ciele żądania POST lub w nagłówku HTTP.
- **Podział `ProfileController`** — kontroler obsługuje zarówno profil użytkownika, jak i operacje na zewnętrznym API. Warto rozdzielić na dwa kontrolery.
- **Kolejkowanie importu** — przy większej liczbie zdjęć synchroniczny import może powodować timeout żądania HTTP. W minimalnym rozwiązaniu wystarczy Symfony Messenger z transportem na tabeli w bazie danych; w bardziej rozbudowanej formie — RabbitMQ lub podobny system kolejkowy.
- **Frontend** — brak RWD, style pisane bezpośrednio w `<style>` w szablonach. Minimalną poprawą byłoby wyodrębnienie CSS do osobnych plików SCSS i zbudowanie ich narzędziem takim jak Webpack lub Gulp z odpowiednimi pluginami.

Oraz szereg innych rzeczy. Istotnym ograniczeniem jest tu czas i cel zadania.

---

## Rozszerzone funkcjonalności

### Endpoint szczegółów zdjęcia w Phoenix API
Istniejący endpoint `/api/photos` zwracał tylko `id` i `photo_url`. Dodano endpoint `/api/photos/:id` zwracający pełne dane: `location`, `camera`, `description`, `taken_at`. Dzięki temu import zdjęć do SymfonyApp uwzględnia wszystkie metadane. Osobny endpoint z pełnymi danymi jest też bardziej elastyczny — może być używany w przyszłości niezależnie od funkcji importu. Oczywiście rozszerzenie istniejącego endpointu byłoby wystarczające przy tak małej skali.

### Reakcja na limity API Phoenix
Po stronie SymfonyApp dodano obsługę odpowiedzi HTTP 429 z Phoenix API. `PhoenixApiClient` rzuca dedykowany `RateLimitExceededException`, który jest łapany w `ProfileController` i prezentowany użytkownikowi jako komunikat: _„Import limit exceeded. You can import photos at most 5 times per 10 minutes."_

### Token dostępu jako osobna encja
Token dostępu do Phoenix API przechowywany jest w encji `ExternalApiToken` (relacja `ManyToOne → User`) zamiast bezpośrednio w tabeli `users`. Decyzja motywowana zasadą pojedynczej odpowiedzialności — token to dane integracyjne, nie dane profilu użytkownika. Rozwiązanie łatwo rozszerzalne na kolejne integracje bez zmiany schematu tabeli `users`.

---

## Testy

Napisano testy trzech rodzajów:

- **Testy jednostkowe** (`tests/Unit/`) — `LikeServiceTest`: mockowanie `LikeRepositoryInterface`, scenariusze sukcesu i wyjątku z repozytorium.
- **Testy integracyjne** (`tests/Integration/`) — `PhotoRepositoryTest`: uruchomione na izolowanej bazie `symfony_app_test`, sprawdzają filtrowanie po każdym polu osobno i w kombinacjach. Każdy test czyści tabele w `setUp()`, dzięki czemu dane developerskie są nienaruszane.
- **Testy ExUnit** (Phoenix) — `RateLimiterTest`: 7 testów jednostkowych dla GenServera sliding window. Technika `:sys.replace_state/2` pozwala symulować wygaśnięcie okna czasowego bez użycia `sleep`, co zapewnia szybkie i deterministyczne testy.

---

## Sposób i stopień wykorzystania AI

Wszystkie zadania były realizowane przy wsparciu GitHub Copilot w trybie agentowym, plan oraz w trybie ask - pozwoliło to w krótki czasie zrealizować wszystkie zadania, także zadanie dotyczące zmian w Phoenix API, które jest zreazlizowane w mniej powszechnej technologii. AI generowało implementacje na podstawie opisów z przygotowanego wcześniej planu, a następnie uruchamiało testy i naprawiało błędy kompilacji lub konfiguracji. Weryfikacja poprawności biznesowej, decyzje architektoniczne, pilnowanie dobrych praktyk oraz korekty kodu były po stronie dewelopera.
