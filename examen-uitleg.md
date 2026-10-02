# PawPal – uitleg per dag

Per dag een korte uitleg van wat er gemaakt is.

---

## Dag 1 – 1 oktober 2026

**Wat is er gemaakt**

- De mappen van het project en een Git-repository.
- De database `pawpal` met zes tabellen: gebruikers, dieren, verzorging, capaciteit, reserveringen en wijzigingslog. Er staan vier testaccounts en drie testdieren in.
- De verbinding tussen PHP en de database.
- De kop, de voet, de opmaak in de PawPal-kleuren en de startpagina.

**Hoe werkt het**

- `includes/db.php` heeft de functie `db()`. Die maakt de verbinding met de database. Iedere pagina die gegevens nodig heeft, gebruikt deze functie. Gaat de verbinding fout, dan ziet de bezoeker alleen een algemene melding en komt de echte fout in het logbestand.
- `includes/functions.php` heeft de functie `e()`. Die maakt tekst veilig met `htmlspecialchars()`, zodat tekst van een gebruiker nooit als code wordt uitgevoerd.
- `includes/config.php` bevat de databasegegevens. Dit bestand staat in `.gitignore` en komt dus niet op GitHub. `config.example.php` is het voorbeeld zonder wachtwoord.
- Iedere pagina laadt eerst `header.php` en aan het eind `footer.php`. Zo staan het menu en de voettekst maar op één plek.
- In de mappen `includes/` en `database/` staat een `.htaccess` die bezoekers tegenhoudt.
- In de database zorgen foreign keys ervoor dat een dier altijd bij een bestaande eigenaar hoort. UNIQUE zorgt dat dezelfde capaciteit of reservering niet twee keer kan bestaan.

**Anders dan in mijn ontwerp**

- Gewone PHP in plaats van Laravel, omdat dat eenvoudiger is om uit te leggen.
- Nederlandse kolomnamen (`eigenaar_id`, `dier_id`, `aangemaakt_op`) in plaats van Engelse.
- Gebruikers hebben een extra kolom `locatie`, nodig voor de regel welke medewerker de verzorgingsgegevens mag zien.
- De wijzigingslog heeft de kolommen `veld`, `oude_waarde`, `nieuwe_waarde` en `dier_naam`, zodat te zien is wat er precies gewijzigd is.

**Getest**

- De database importeert zonder fouten en weigert dubbele of ongeldige gegevens.
- Het wachtwoord `Test1234!` klopt bij alle vier de testaccounts.
- `includes/` en `database/` zijn niet te openen in de browser (403).
- De startpagina werkt op computer en op telefoonbreedte.

**Eisen en planning:** TE-01, TE-02, TE-05 en het begin van TE-04. Planningstaak T-06.

---

## Dag 2 – 2 oktober 2026

**Wat is er gemaakt**

- Een loginpagina voor eigenaren en medewerkers.
- Uitloggen met een knop in het menu.
- Een dashboard voor de eigenaar en een dashboard voor de medewerker.
- Beveiliging van pagina's op rol en CSRF-tokens in de formulieren.

**Hoe werkt het**

- Alle functies voor inloggen staan in `includes/auth.php`. Dat bestand start ook de sessie. Een sessie onthoudt op de server wie er is ingelogd; de browser krijgt alleen een cookie met een sessienummer.
- `inloggen()` zoekt de gebruiker op met een prepared statement (`prepare()` en `execute()`), zodat SQL-injectie niet kan. Daarna vergelijkt `password_verify()` het ingevulde wachtwoord met de hash uit de database.
- Na goed inloggen maakt `session_regenerate_id()` een nieuw sessienummer. Een oud of gestolen nummer werkt daarna niet meer. In `$_SESSION['gebruiker']` staan het id, de naam, de rol en de locatie.
- Bij een fout wachtwoord of onbekend e-mailadres staat er altijd dezelfde melding: "Inloggegevens zijn onjuist." Zo kan niemand zien of een e-mailadres bestaat.
- `vereis_rol('eigenaar')` staat bovenaan iedere beveiligde pagina. Wie niet is ingelogd gaat naar de loginpagina. Wie de verkeerde rol heeft, gaat terug naar zijn eigen dashboard.
- `csrf_veld()` zet een geheim token in ieder formulier en `controleer_csrf()` controleert het bij het versturen. Een andere website kent dat token niet en kan dus geen formulier namens de gebruiker versturen.
- Uitloggen gaat met een formulier (POST) en niet met een gewone link, zodat een andere website iemand niet ongemerkt kan uitloggen. `uitloggen()` maakt de sessie leeg en verwijdert de cookie.
- `doorsturen()` in `functions.php` stuurt de bezoeker naar een andere pagina en stopt het script.

**Anders dan in mijn ontwerp**

- Geen afwijkingen. Registratie is nog niet gebouwd; dat moet ik nog beslissen.

**Getest**

- Eigenaar en medewerker kunnen inloggen en komen op hun eigen dashboard.
- Fout wachtwoord, onbekend e-mailadres en lege velden geven een foutmelding.
- Een eigenaar kan het dashboard van de medewerker niet openen, en andersom.
- Zonder inloggen zijn de dashboards niet te openen.
- Een formulier zonder CSRF-token wordt geweigerd.
- Een SQL-injectie in het e-mailveld werkt niet, en HTML in het e-mailveld wordt als gewone tekst getoond.
- Het sessienummer verandert na het inloggen.
- Na uitloggen is het dashboard niet meer te openen.
- De loginpagina en het dashboard werken op computer en op telefoonbreedte.

**Eisen en planning:** TE-03, een deel van FE-07 (rolcontrole) en TE-04 (invoer controleren, veilige uitvoer, foutmeldingen). Planningstaak T-07.
