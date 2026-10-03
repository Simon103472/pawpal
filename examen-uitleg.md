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

---

## Dag 3 – 3 oktober 2026

**Wat is er gemaakt**

- De pagina "Mijn dieren": dieren bekijken als kaarten, toevoegen, wijzigen en verwijderen.
- De pagina "Verzorging": per dier voeding, contactpersoon en bijzonderheden opslaan en aanpassen.
- Het dashboard van de eigenaar laat nu de eigen dieren zien.
- Meldingen na een actie, bijvoorbeeld "Het dier is verwijderd."

**Hoe werkt het**

- `eigen_dier()` in `functions.php` haalt een dier op met `WHERE id = ? AND eigenaar_id = ?`. Het nummer van het dier staat in de URL en kan dus worden veranderd. Omdat de query ook op de eigenaar zoekt, krijgt iemand nooit het dier van een ander. Dan volgt de melding "Dit dier is niet gevonden."
- Ook de UPDATE en DELETE hebben `AND eigenaar_id = ?`. De controle zit dus op twee plekken.
- `eigenaar/dieren.php` doet drie dingen. Bij `actie=opslaan` wordt de invoer gecontroleerd en volgt een INSERT (nieuw dier) of een UPDATE (bestaand dier). Bij `actie=verwijderen` volgt een DELETE. Zonder formulier toont de pagina de kaarten en het formulier.
- De invoer wordt op de server gecontroleerd: naam verplicht en maximaal 100 tekens, diersoort moet in de vaste lijst `DIERSOORTEN` staan, en de geboortedatum moet een echte datum zijn die niet in de toekomst ligt. Bij een fout wordt niets opgeslagen en blijft de ingevulde tekst in het formulier staan.
- `geldige_datum()` controleert of een datum echt bestaat. 31 februari wordt geweigerd.
- Verwijderen gaat in twee stappen: eerst de vraag "Weet je zeker...?", daarna pas het echte verwijderen met een POST-formulier. Een dier met een reservering die nog komt, kan niet worden verwijderd.
- Bij het verwijderen van een dier verdwijnen de verzorgingsgegevens vanzelf mee door `ON DELETE CASCADE` in de database.
- `eigenaar/verzorging.php` kijkt of er al een rij voor het dier bestaat. Zo ja dan UPDATE, anders INSERT. Bij een fout blijven de oude gegevens in de database staan.
- `zet_melding()` bewaart een melding in de sessie. Na het doorsturen toont `header.php` de melding één keer en haalt hem weg.
- Na het opslaan wordt de gebruiker doorgestuurd. Daardoor wordt het formulier niet nog een keer verstuurd als hij de pagina ververst.

**Anders dan in mijn ontwerp**

- Verwijderen heeft een extra bevestigingsstap en wordt geblokkeerd bij een reservering die nog komt. Dat stond niet in het ontwerp.
- Voeding en contactpersoon zijn verplichte velden; bijzonderheden niet.

**Getest**

- Eva ziet alleen Max en Luna, niet het dier van Tom.
- Eva kan het dier van Tom niet openen, wijzigen of verwijderen, ook niet door het nummer in de URL of in het formulier te veranderen. De database is daarna onveranderd.
- Lege naam, onbekende diersoort, datum in de toekomst, niet-bestaande datum en te lange naam worden geweigerd.
- Dier toevoegen, wijzigen en verwijderen werkt; de gegevens staan goed in de database.
- HTML in de naam van een dier wordt als gewone tekst getoond.
- Verzorging opslaan en aanpassen werkt en staat er na opnieuw openen nog.
- Een dier met een open reservering kan niet worden verwijderd; na afwijzen wel.
- Een medewerker kan de pagina's van de eigenaar niet openen.
- De pagina's werken op computer en op telefoonbreedte.

**Eisen en planning:** FE-01, FE-02, het eigenaar-deel van FE-07 en TE-04. Planningstaken T-08 en T-09.
