# PawPal – Pet Care Planner

PawPal is een webapplicatie voor Dierenopvangservice Happy Tails. Huisdiereigenaren beheren hun dieren en verzorgingsgegevens en vragen opvang aan. Opvangmedewerkers beheren de capaciteit, beoordelen aanvragen en bekijken de dagplanning.

Gemaakt door Simon Singh als examenproject Software Developer (mbo 4). De applicatie bevat alleen testgegevens.

## Wat kan de applicatie

| Eis | Functie | Pagina |
|---|---|---|
| FE-01 | Eigenaar beheert eigen dierenprofielen | `eigenaar/dieren.php` |
| FE-02 | Eigenaar legt voeding, contactpersoon en bijzonderheden vast | `eigenaar/verzorging.php` |
| FE-03 | Eigenaar reserveert opvang; een vol moment wordt geweigerd | `eigenaar/reserveringen.php` |
| FE-04 | Medewerker stelt capaciteit in; vrije plekken worden berekend | `medewerker/capaciteit.php` |
| FE-05 | Medewerker keurt aanvragen goed of wijst ze af | `medewerker/aanvragen.php` |
| FE-06 | Medewerker bekijkt de dagplanning met filters op locatie en dienst | `medewerker/dagplanning.php` |
| FE-07 | Verzorgingsgegevens zijn alleen zichtbaar voor de eigenaar en de betrokken medewerker | `medewerker/verzorging.php` |
| FE-08 | Wijzigingen in verzorgingsgegevens worden geregistreerd | `medewerker/wijzigingslog.php` |

## Benodigdheden

- PHP 8.0 of hoger met de extensies `pdo_mysql` en `mbstring` (ontwikkeld met PHP 8.2.12)
- MySQL of MariaDB (ontwikkeld met MariaDB 10.4.32)
- Apache met `.htaccess`-ondersteuning (bijvoorbeeld XAMPP)

Er zijn geen frameworks, Composer-pakketten of JavaScript-bibliotheken nodig.

## Installatie (lokaal met XAMPP)

1. Zet de map `pawpal` in `C:\xampp\htdocs\`.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Open <http://localhost/phpmyadmin> en maak een lege database `pawpal` aan met collatie `utf8mb4_unicode_ci`.
4. Klik de database aan, kies **Importeren** en importeer `database/pawpal.sql`. Dit maakt de zes tabellen en de testgegevens aan.
5. Kopieer `includes/config.example.php` naar `includes/config.php` en vul de gegevens van je database in. Bij een standaard XAMPP is de gebruiker `root` met een leeg wachtwoord.
6. Controleer `BASIS_URL` in `includes/config.php`: `/pawpal` als de site in de map `pawpal` staat, of een lege tekst `''` als de site direct in de hoofdmap van het domein staat.
7. Open <http://localhost/pawpal/>.

`includes/config.php` staat in `.gitignore` en zit dus niet in de repository.

## Installatie op Plesk

1. **Database aanmaken:** ga in Plesk naar **Databases → Database toevoegen**. Kies een databasenaam, maak een databasegebruiker met een sterk wachtwoord en noteer de naam, de gebruiker, het wachtwoord en de hostnaam.
2. **Tabellen importeren:** klik bij de database op **phpMyAdmin**, kies **Importeren** en importeer `database/pawpal.sql`.
3. **PHP-versie:** ga naar **PHP-instellingen** van het domein en kies PHP 8.0 of hoger.
4. **Bestanden uploaden:** zet de inhoud van de map `pawpal` in de map `httpdocs` van het domein, via **Bestanden** in Plesk of via FTP. Een ZIP uploaden en in Plesk uitpakken kan ook.
5. **Instellingen:** kopieer op de server `includes/config.example.php` naar `includes/config.php` en vul de gegevens van stap 1 in. Zet `BASIS_URL` op een lege tekst (`''`) als de site direct in `httpdocs` staat.
6. **Controleren:**
   - De startpagina opent via `https://` en inloggen met een testaccount werkt.
   - `https://jouwdomein/includes/config.php` en `https://jouwdomein/database/pawpal.sql` geven "403 Forbidden". Is dat niet zo, dan worden `.htaccess`-bestanden niet gebruikt en moeten deze mappen op een andere manier worden afgeschermd.

## Testaccounts

Het wachtwoord van alle testaccounts is `Test1234!`.

| E-mailadres | Rol | Bijzonderheid |
|---|---|---|
| eva@pawpal.test | Eigenaar | Dieren Max (hond) en Luna (kat) |
| tom@pawpal.test | Eigenaar | Dier Flappie (konijn) |
| noord@pawpal.test | Medewerker | Locatie Noord |
| zuid@pawpal.test | Medewerker | Locatie Zuid |

## Korte route om de applicatie te controleren

1. Log in als **Eva**, ga naar **Reserveringen**, kies Max en vraag een opvangmoment op locatie Noord aan.
2. Log in als **Medewerker Noord**, ga naar **Aanvragen** en keur de aanvraag goed.
3. Ga naar **Dagplanning**, kies de datum van de reservering en klik bij Max op **Bekijken** om de verzorgingsgegevens te zien.
4. Log in als **Medewerker Zuid** en open dezelfde dagplanning met "Alle locaties": bij Max staat nu "Geen toegang".
5. Log in als **Eva**, pas bij **Mijn dieren → Verzorging** de voeding van Max aan. Log in als **Medewerker Noord** en bekijk de **Wijzigingslog**.
6. Log in als **Tom**: de dieren en reserveringen van Eva zijn niet zichtbaar.

De testgegevens terugzetten kan door `database/pawpal.sql` opnieuw te importeren.

## Mappenstructuur

```
pawpal/
├── index.php, login.php, logout.php
├── assets/css/style.css        opmaak
├── includes/                   gedeelde code, niet bereikbaar via de browser
│   ├── config.example.php      voorbeeld van de instellingen
│   ├── db.php                  databaseverbinding (PDO)
│   ├── auth.php                inloggen, rollen en CSRF-beveiliging
│   ├── functions.php           hulpfuncties
│   └── header.php, footer.php  boven- en onderkant van iedere pagina
├── eigenaar/                   pagina's voor de huisdiereigenaar
├── medewerker/                 pagina's voor de opvangmedewerker
└── database/pawpal.sql         tabellen en testgegevens
```

## Beveiliging

- Wachtwoorden worden opgeslagen als hash (`password_hash()`), nooit leesbaar.
- Alle query's gebruiken prepared statements (PDO).
- Alle tekst van gebruikers wordt getoond via `htmlspecialchars()`.
- Iedere beveiligde pagina controleert de rol; iedere actie op een dier controleert de eigenaar.
- Formulieren die gegevens wijzigen hebben een CSRF-token.
- Reserveren gebeurt in een transactie met `SELECT ... FOR UPDATE`, zodat een plek niet twee keer kan worden vergeven.

## Verschillen tussen het ontwerp en de applicatie

| Nr | In het ontwerp of de planning | In de applicatie | Reden |
|---|---|---|---|
| 1 | "PHP/Laravel-project" (taak T-06) | Gewone PHP zonder framework | Eenvoudiger te bouwen en uit te leggen; voldoet aan TE-01 en TE-02 |
| 2 | Projectperiode van vijf weken | Gebouwd in acht dagen (1 t/m 8 oktober 2026) | De planning is ingekort; de taken T-06 t/m T-20 zijn gelijk gebleven |
| 3 | Testen pas in week 5 | Iedere dag direct getest, plus een volledige testronde op dag 7 | Fouten worden eerder gevonden |
| 4 | Controle op vrije plekken (T-12) na het reserveringsformulier | Tegelijk met het formulier gebouwd | Zonder die controle zou overboeken mogelijk zijn |
| 5 | Kolomnamen `owner_id`, `pet_id`, `capacity_id`, `user_id`, `created_at`, `updated_at` | `eigenaar_id`, `dier_id`, `capaciteit_id`, `gebruiker_id`, `aangemaakt_op`, `bijgewerkt_op` | Alles in het Nederlands, net als de tabelnamen |
| 6 | Tabel gebruikers zonder locatie | Extra kolom `locatie` (Noord of Zuid) bij medewerkers | Nodig voor de toegangsregel van FE-07 |
| 7 | Wijzigingslog met alleen een kolom `actie` | Kolommen `veld`, `oude_waarde`, `nieuwe_waarde` en `dier_naam` | FE-08 vraagt wat er precies is gewijzigd |
| 8 | "Betrokken medewerker" niet verder uitgelegd | Toegang bij een goedgekeurde reservering op de eigen locatie, vandaag of later | Een eenvoudige regel die te testen is |
| 9 | Medewerker kan verzorgingsgegevens "beheren" | Medewerker kan ze alleen bekijken | FE-02 legt het aanpassen bij de eigenaar |
| 10 | Niet beschreven | De inhoud van de wijzigingslog is afgeschermd volgens dezelfde regel als de verzorgingsgegevens | Anders is FE-07 via de log te omzeilen |
| 11 | Mappenstructuur zonder deze pagina's | Extra pagina's `medewerker/verzorging.php` en `medewerker/wijzigingslog.php` | Nodig voor FE-07 en FE-08 |
| 12 | Niet beschreven | Dier verwijderen vraagt eerst om bevestiging en kan niet bij een reservering die nog komt; de logregel blijft bestaan | Voorkomt reserveringen zonder dier en een onleesbare log |
| 13 | Niet beschreven | Een dier kan per datum en dienst maar op één locatie reserveren; een afgewezen aanvraag kan niet opnieuw voor hetzelfde moment | Voorkomt dubbele plekken voor hetzelfde dier |
| 14 | Dagplanning filtert op locatie en dienst | Ook een filter op datum; alleen goedgekeurde reserveringen | Een dagplanning heeft een dag nodig |
| 15 | Vaste waarden niet vastgelegd | Diensten ochtend/middag, locaties Noord/Zuid, diersoorten hond/kat/konijn | Vaste keuzes voorkomen typfouten en maken filteren mogelijk |
| 16 | Dagplanning noemt "registratie" | Geen registratiepagina; accounts worden in de database aangemaakt | Geen functionele eis vraagt erom; het ontwerp heeft alleen een loginscherm |
| 17 | Vier kleuren | Dezelfde vier kleuren, aangevuld met lichtere en donkerdere tinten en een logo | Verzorgder uiterlijk; de hoofdkleuren zijn gelijk gebleven |

## Testrapport

Testronde van 7 oktober 2026 (planningstaken T-16 t/m T-20). Omgeving: Windows, XAMPP, PHP 8.2.12, MariaDB 10.4.32.

**Hoe is er getest**

- Met de vier testaccounts (twee eigenaren, twee medewerkers) en alleen testgegevens.
- De functies zijn getest door verzoeken naar de site te sturen en daarna het scherm en de database te controleren. Daarbij is ook geprobeerd de formulieren te omzeilen (nummer in de URL veranderen, formulier rechtstreeks versturen).
- De schermen zijn bekeken in de browser op drie breedtes: telefoon (375 px), tablet (768 px) en computer. Dit was een nagebootste schermbreedte in de browser, geen echt toestel.
- Na de laatste aanpassing van dag 7 zijn alle tests opnieuw uitgevoerd.

Resultaat: alle onderstaande tests zijn geslaagd.

### Functionele eisen

| Nr | Eis | Test | Verwacht | Resultaat |
|---|---|---|---|---|
| 1 | FE-01 | Eva bekijkt "Mijn dieren" | Alleen Max en Luna, niet het dier van Tom | Geslaagd |
| 2 | FE-01 | Dier toevoegen, wijzigen en verwijderen | Gegevens staan goed in de database; na verwijderen is het dier weg | Geslaagd |
| 3 | FE-01 | Lege naam, onbekende diersoort, datum in de toekomst, 31 februari, naam van 101 tekens | Geweigerd met melding; ingevulde tekst blijft staan | Geslaagd |
| 4 | FE-01, FE-07 | Eva opent, wijzigt en verwijdert het dier van Tom via URL en via het formulier | "Dit dier is niet gevonden"; database onveranderd | Geslaagd |
| 5 | FE-01 | Dier met een reservering die nog komt verwijderen | Geweigerd; na afwijzen lukt het wel | Geslaagd |
| 6 | FE-02 | Verzorging invullen, opslaan, opnieuw openen en aanpassen | Gegevens blijven bewaard en worden goed getoond | Geslaagd |
| 7 | FE-02 | Verzorging opslaan met lege verplichte velden | Geweigerd; oude gegevens blijven in de database | Geslaagd |
| 8 | FE-03 | Eva reserveert voor Max op een moment met vrije plek | Reservering opgeslagen met status "aangevraagd" | Geslaagd |
| 9 | FE-03 | Reserveren op een vol moment, ook rechtstreeks zonder de keuzelijst | "Er is geen vrije plaats meer"; niets opgeslagen | Geslaagd |
| 10 | FE-03 | Tien sessies reserveren tegelijk de laatste plek (5 rondes) | Steeds precies 1 reservering, 9 keer "vol" | Geslaagd |
| 11 | FE-03 | Hetzelfde dier twee keer hetzelfde moment, of dezelfde datum en dienst op de andere locatie | Geweigerd met melding | Geslaagd |
| 12 | FE-03 | Kat reserveren op een plek voor honden; reserveren voor het dier van een ander | Geweigerd | Geslaagd |
| 13 | FE-04 | Medewerker stelt capaciteit in; dezelfde combinatie opnieuw opslaan | Opgeslagen; tweede keer wordt het aantal aangepast, geen dubbele rij | Geslaagd |
| 14 | FE-04 | Negatief aantal, tekst, kommagetal, datum in het verleden, onbekende keuze | Geweigerd met melding | Geslaagd |
| 15 | FE-04 | Overzicht na een reservering; aantal lager zetten dan bezet | Bezet en vrij kloppen ("vol" bij 0 vrij); verlagen geweigerd | Geslaagd |
| 16 | FE-05 | Medewerker keurt een aanvraag goed en wijst een andere af | Status verandert; eigenaar ziet de nieuwe status | Geslaagd |
| 17 | FE-05, FE-04 | Vrije plekken na afwijzen | De plek is weer vrij en opnieuw te reserveren | Geslaagd |
| 18 | FE-05 | Aanvraag die al beoordeeld is nog een keer beoordelen; twee medewerkers tegelijk | Eén actie lukt; de ander krijgt "al beoordeeld" | Geslaagd |
| 19 | FE-06 | Dagplanning filteren: eigen locatie, alle locaties, Zuid, Noord + ochtend, Noord + middag, alle + middag | Alleen passende goedgekeurde reserveringen | Geslaagd |
| 20 | FE-06 | Dag zonder reserveringen; ongeldige datum | Duidelijke melding in plaats van een lege of kapotte pagina | Geslaagd |
| 21 | FE-07 | Medewerker Noord opent verzorging van Max (goedgekeurd op Noord) | Gegevens zichtbaar | Geslaagd |
| 22 | FE-07 | Medewerker Noord opent verzorging van: dier op Zuid, dier dat alleen is aangevraagd, dier met reservering van gisteren, onbestaand dier | "Je hebt geen toegang"; geen gegevens zichtbaar | Geslaagd |
| 23 | FE-07 | Toegang na afwijzen van de reservering | Toegang vervalt | Geslaagd |
| 24 | FE-07 | Eigenaar opent pagina's van de medewerker; medewerker opent pagina's van de eigenaar of wijzigt verzorging | Teruggestuurd naar het eigen dashboard; niets gewijzigd | Geslaagd |
| 25 | FE-07 | Tom bekijkt zijn reserveringen | Ziet de reserveringen van Eva niet | Geslaagd |
| 26 | FE-08 | Eén verzorgingsveld aanpassen | Eén logregel met gebruiker, dier, veld, oude waarde, nieuwe waarde en tijdstip | Geslaagd |
| 27 | FE-08 | Opslaan zonder verandering; twee velden aanpassen; ongeldige invoer | 0 logregels; 2 logregels; 0 logregels | Geslaagd |
| 28 | FE-08, FE-07 | Wijzigingslog bekijken als medewerker Noord en als medewerker Zuid | Wie/wanneer/dier/veld altijd zichtbaar; inhoud alleen bij dieren op de eigen locatie, anders "Afgeschermd" | Geslaagd |
| 29 | FE-08 | Dier verwijderen waarvan een logregel bestaat | Logregel blijft staan met de naam en "(verwijderd)" | Geslaagd |

### Technische eisen

| Nr | Eis | Test | Verwacht | Resultaat |
|---|---|---|---|---|
| 30 | TE-01 | PHP-versie opvragen | 8 of hoger | Geslaagd (8.2.12) |
| 31 | TE-02 | Gegevens opslaan, pagina opnieuw laden, database bekijken | Gegevens staan in de zes tabellen van MariaDB | Geslaagd |
| 32 | TE-03 | Inloggen als eigenaar en als medewerker | Ieder komt op het eigen dashboard | Geslaagd |
| 33 | TE-03 | Fout wachtwoord, onbekend e-mailadres, lege velden | Algemene melding "Inloggegevens zijn onjuist" | Geslaagd |
| 34 | TE-03 | Kolom `wachtwoord_hash` bekijken | Alleen hashes (`$2y$...`, 60 tekens); wachtwoord nergens leesbaar | Geslaagd |
| 35 | TE-03 | Sessienummer voor en na inloggen; uitloggen; dashboard openen zonder inloggen | Nieuw sessienummer na inloggen; daarna geen toegang meer; doorgestuurd naar login | Geslaagd |
| 36 | TE-03 | Formulier versturen zonder CSRF-token (inloggen, uitloggen, dier, verzorging, reservering, capaciteit, aanvraag) | Geweigerd (foutcode 400); niets gewijzigd | Geslaagd |
| 37 | TE-04 | SQL-injectie in het e-mailveld en in het filter van de dagplanning | Geen effect | Geslaagd |
| 38 | TE-04 | HTML en JavaScript invullen als diernaam, e-mail en voeding | Wordt als gewone tekst getoond, niet uitgevoerd | Geslaagd |
| 39 | TE-04 | Alle pagina's op 375 px, 768 px en computerbreedte | Leesbaar, niet opzij schuiven, tabellen worden blokjes op smalle schermen | Geslaagd |
| 40 | TE-04 | Databaseverbinding met fout wachtwoord; query op een tabel die niet bestaat | Bezoeker ziet alleen een algemene melding; de echte fout staat in het logbestand | Geslaagd |
| 41 | TE-04 | `includes/` en `database/` openen in de browser | 403 Forbidden | Geslaagd |
| 42 | TE-05 | Dier zonder bestaande eigenaar, dubbele capaciteit, dubbele reservering rechtstreeks in de database | Geweigerd door foreign key of UNIQUE | Geslaagd |
| 43 | TE-05 | Dier verwijderen met verzorging, reserveringen en logregels | Verzorging en reserveringen weg (CASCADE); logregel blijft (SET NULL) | Geslaagd |
| 44 | TE-05 | Controleproef: reserveren zonder `FOR UPDATE` in een tijdelijke kopie, met vertraging | Zonder slot 10 reserveringen op 1 plek; met slot 1. Het slot voorkomt dus overboeking | Geslaagd |

### Gevonden problemen en oplossingen

| Dag | Probleem | Oplossing |
|---|---|---|
| 3 | De vraag "Weet je zeker?" stond tegen de kaarten eronder aan | Ruimte onder meldingen toegevoegd in de CSS |
| 4 | Een eerder afgewezen aanvraag opnieuw doen gaf de onduidelijke melding "heeft al een reservering" | Eigen melding: "De aanvraag voor dit opvangmoment is eerder afgewezen" |
| 5 | De test met tien tegelijk liet zonder slot geen verschil zien, omdat de aanvragen te snel klaar waren | Controleproef herhaald met een kleine vertraging in een tijdelijke kopie; toen was het verschil duidelijk (test 44) |
| 7 | Op telefoon en tablet moest je de tabel opzij schuiven om de knoppen Goedkeuren en Afwijzen te zien | Tabellen worden op schermen tot 800 px als blokjes getoond |
| 7 | Bij een fout die nergens werd opgevangen kon PHP technische details op het scherm zetten | Functie `onverwachte_fout()` toegevoegd: algemene melding voor de bezoeker, echte fout in het logbestand |
| 7 | Het dashboard van de eigenaar liet geen reserveringen zien, terwijl dat in het ontwerp staat | Aantal reserveringen per status toegevoegd, met een link naar de reserveringen |

### Nog niet getest

- Op een echte telefoon en een echte tablet (alleen nagebootst in de browser).
- Op de Plesk-omgeving. Dat gebeurt op dag 8 na het uploaden.

## Meer uitleg

In `examen-uitleg.md` staat per dag wat er gebouwd is en hoe het werkt.
