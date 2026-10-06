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

---

## Dag 4 – 4 oktober 2026

**Wat is er gemaakt**

- De pagina "Capaciteit" voor de medewerker: per datum, dienst, locatie en diersoort het aantal plekken instellen, met een overzicht van bezette en vrije plekken.
- De pagina "Reserveringen" voor de eigenaar: een eigen dier kiezen, een opvangmoment met vrije plekken kiezen en de reservering aanvragen.
- Een overzicht van de eigen reserveringen met de status in kleur en tekst.

**Hoe werkt het**

- `DIENSTEN` en `LOCATIES` in `functions.php` zijn de vaste keuzes. De invoer wordt met `in_array()` tegen die lijsten gecontroleerd.
- `bezette_plaatsen()` telt de reserveringen met status aangevraagd of goedgekeurd. Een afgewezen reservering telt niet mee.
- `capaciteit_overzicht()` haalt de capaciteit vanaf vandaag op en rekent uit: vrij = plekken − bezet. Dezelfde functie wordt door de medewerker (overzicht) en de eigenaar (keuzelijst) gebruikt.
- Capaciteit opslaan: bestaat de combinatie al, dan wordt het aantal aangepast (UPDATE); anders komt er een nieuwe rij (INSERT). Het aantal moet een heel getal van 0 tot 100 zijn (`ctype_digit()`), de datum mag niet in het verleden liggen, en het aantal mag niet lager worden dan wat al bezet is.
- Reserveren gaat in twee stappen. Stap 1 kiest het dier (met `method="get"`, er wordt nog niets opgeslagen). Stap 2 toont alleen opvangmomenten voor de diersoort van dat dier waar nog plek is.
- `maak_reservering()` gebruikt een transactie. `SELECT ... FOR UPDATE` zet de capaciteitsrij op slot. Daarna telt de functie de bezette plekken en slaat de reservering alleen op als er nog plek is. Willen twee mensen tegelijk de laatste plek, dan moet de tweede wachten en krijgt die daarna de melding dat het vol is.
- Een transactie betekent: alle stappen lukken samen (`commit`) of er gebeurt niets (`rollBack`).
- De server controleert alles opnieuw, ook als iemand het formulier omzeilt: is het dier van deze eigenaar, past de diersoort bij het opvangmoment, is er nog plek, en heeft het dier op die datum en dienst nog geen reservering.
- Een nieuwe reservering krijgt vanzelf de status "aangevraagd" (standaardwaarde in de database).

**Anders dan in mijn ontwerp**

- De controle op vrije plekken (taak T-12) is nu al gebouwd, samen met het formulier. Een formulier zonder die controle zou overboeken mogelijk maken.
- Een dier kan op dezelfde datum en dienst maar op één locatie reserveren. Dat stond niet in het ontwerp.
- Een afgewezen aanvraag kan niet opnieuw worden gedaan voor precies hetzelfde opvangmoment.

**Getest**

- Een eigenaar kan de capaciteitspagina niet openen en er niets naartoe versturen.
- Negatief aantal, tekst, kommagetal, datum in het verleden en onbekende keuzes worden geweigerd.
- Capaciteit toevoegen werkt; dezelfde combinatie opnieuw opslaan past het aantal aan en maakt geen tweede rij.
- Het aantal kan niet lager worden dan het aantal bezette plekken.
- Een eigenaar kan niet reserveren voor het dier van een ander, ook niet via de URL of het formulier.
- Een kat kan niet op een plek voor honden reserveren.
- Op een volle plek kan niet worden gereserveerd, ook niet door het formulier te omzeilen. De volle plek staat niet meer in de keuzelijst.
- Hetzelfde dier kan hetzelfde moment niet twee keer reserveren, en ook niet dezelfde datum en dienst op een andere locatie.
- Een eigenaar ziet alleen zijn eigen reserveringen.
- Na afwijzen is de plek in het overzicht weer vrij.
- De pagina's werken op computer en op telefoonbreedte; de tabel schuift op een telefoon zelf opzij.

Nog niet getest: twee reserveringen op precies hetzelfde moment (staat op dag 5).

**Eisen en planning:** FE-03, FE-04, TE-05 (transactie en unieke combinaties) en TE-04. Planningstaken T-10 en T-11, en het bouwen van T-12.

---

## Dag 5 – 5 oktober 2026

**Wat is er gemaakt**

- De pagina "Aanvragen" voor de medewerker: open aanvragen bekijken, goedkeuren en afwijzen, en een lijst met aanvragen die al beoordeeld zijn.
- Het dashboard van de medewerker laat zien hoeveel aanvragen nog open staan.
- De test met meerdere reserveringen op precies hetzelfde moment.

**Hoe werkt het**

- Elke rij in de tabel heeft een eigen formulier met twee knoppen. De knop waarop geklikt wordt, stuurt zijn waarde mee als `actie`: `goedkeuren` of `afwijzen`. Een andere waarde wordt geweigerd.
- De status wordt aangepast met `UPDATE reserveringen SET status = ? WHERE id = ? AND status = 'aangevraagd'`. Door dat laatste stuk kan alleen een open aanvraag worden beoordeeld.
- `rowCount()` geeft het aantal aangepaste rijen. Is dat 0, dan was de aanvraag al beoordeeld of bestaat hij niet. De medewerker krijgt dan de melding "Deze aanvraag is al beoordeeld of bestaat niet meer."
- Klikken twee medewerkers tegelijk op dezelfde aanvraag, dan wint de eerste. De tweede krijgt de melding hierboven. Daar is geen transactie voor nodig, omdat het één UPDATE is.
- Goedkeuren verandert niets aan het aantal vrije plekken: een aangevraagde reservering hield de plek al bezet. Afwijzen maakt de plek weer vrij, omdat `bezette_plaatsen()` afgewezen reserveringen niet meetelt.
- Alle medewerkers mogen aanvragen van beide locaties beoordelen.
- De pagina toont alleen het dier, de eigenaar en het opvangmoment. Verzorgingsgegevens worden hier niet opgehaald.

**Anders dan in mijn ontwerp**

- Geen afwijkingen. Een beoordeelde aanvraag kan niet meer worden teruggedraaid; dat stond niet in het ontwerp, maar past bij "dubbele actie wordt netjes afgehandeld".

**Getest**

- Tien sessies proberen tegelijk de laatste plek te reserveren, vijf keer achter elkaar: steeds precies 1 reservering in de database en 9 keer de melding dat het vol is.
- Controleproef: in een tijdelijke kopie zonder `FOR UPDATE` (met een kleine vertraging) kwamen er 10 reserveringen op 1 plek. Met `FOR UPDATE` en dezelfde vertraging bleef het 1. Dat bewijst dat het slot de overboeking voorkomt. De kopie is daarna verwijderd.
- Een eigenaar kan de aanvragenpagina niet openen en kan zijn eigen aanvraag niet goedkeuren.
- Goedkeuren en afwijzen veranderen de status; de eigenaar ziet de nieuwe status in zijn eigen overzicht.
- Na afwijzen is de plek weer vrij en kan er opnieuw op worden gereserveerd.
- Een aanvraag die al beoordeeld is, kan niet nog een keer worden beoordeeld.
- Twee medewerkers die tegelijk goedkeuren en afwijzen: één actie lukt, de ander krijgt een nette melding.
- Een onbekende actie, een niet-bestaande aanvraag en een formulier zonder CSRF-token worden geweigerd.
- Er staan geen verzorgingsgegevens op de aanvragenpagina.
- De pagina werkt op computer en op telefoonbreedte. Op een telefoon moet de tabel opzij worden geschoven om de knoppen te zien.

**Eisen en planning:** FE-05, FE-03 en FE-04 (geen overboeking), TE-05. Planningstaken T-12 (test) en T-13.

---

## Dag 6 – 6 oktober 2026

**Wat is er gemaakt**

- De pagina "Dagplanning" voor de medewerker, met filters op datum, locatie en dienst.
- Een pagina waarop de medewerker de verzorgingsgegevens van een dier kan bekijken, maar alleen als hij bij de opvang van dat dier betrokken is.
- De wijzigingsregistratie: bij iedere aanpassing van verzorgingsgegevens wordt vastgelegd wie het deed, wanneer en wat er veranderde.
- De pagina "Wijzigingslog" voor de medewerker.

**Hoe werkt het**

- De dagplanning toont alleen goedgekeurde reserveringen van de gekozen dag. De filters staan in de URL (`method="get"`). Zonder keuze zie je vandaag en je eigen locatie.
- De query wordt stap voor stap opgebouwd: elk gekozen filter voegt `AND c.locatie = ?` of `AND c.dienst = ?` toe. De waarde zelf gaat apart mee in `execute()`. Een onbekende waarde (bijvoorbeeld locatie "Oost") telt als "geen filter".
- De toegangsregel staat in één functie: `medewerker_mag_verzorging_zien()`. Een medewerker mag de verzorgingsgegevens zien als het dier een **goedgekeurde** reservering heeft op **zijn locatie**, **vandaag of later**. In alle andere gevallen krijgt hij "Je hebt geen toegang tot de verzorgingsgegevens van dit dier."
- `medewerker/verzorging.php` controleert die regel bovenaan de pagina. Het nummer van het dier in de URL veranderen helpt dus niet.
- De medewerker kan de verzorgingsgegevens alleen lezen. Aanpassen kan alleen de eigenaar.
- In `eigenaar/verzorging.php` wordt bij het opslaan per veld de oude waarde met de nieuwe vergeleken. Alleen een veld dat echt anders is, krijgt een regel in de wijzigingslog via `log_wijziging()`. Opslaan zonder iets te veranderen geeft dus geen logregel.
- Het opslaan van de verzorging en van de logregels zit samen in één transactie. Mislukt er iets, dan wordt alles teruggedraaid. Er kan dus geen wijziging zijn zonder logregel.
- Een logregel bevat: wie (`gebruiker_id`), welk dier (`dier_id` en `dier_naam`), welk veld, de oude waarde, de nieuwe waarde en het tijdstip. Het tijdstip vult de database zelf in.
- De oude en nieuwe waarde zijn zelf ook verzorgingsgegevens. Daarom geldt in de wijzigingslog dezelfde toegangsregel: iedere medewerker ziet wie, wanneer, welk dier en welk veld, maar de inhoud staat op "Afgeschermd" als hij niet bij dat dier betrokken is.
- Wordt een dier verwijderd, dan blijft de logregel staan met de naam en "(verwijderd)" erachter. De inhoud is dan voor iedereen afgeschermd.
- `nl2br()` laat een nieuwe regel in een tekst ook als nieuwe regel zien. Het staat om `e()` heen, zodat de tekst eerst veilig wordt gemaakt.

**Anders dan in mijn ontwerp**

- De wijzigingslog heeft een eigen pagina `medewerker/wijzigingslog.php` en de medewerker heeft een eigen pagina `medewerker/verzorging.php`. Die stonden niet in de mappenstructuur.
- In het ontwerp staat bij verzorgingsgegevens "beheren" door eigenaar en betrokken medewerker. In de applicatie kan de medewerker alleen bekijken, niet aanpassen.
- De inhoud van de wijzigingslog is afgeschermd volgens dezelfde regel als de verzorgingsgegevens. Dat stond niet in het ontwerp.
- De dagplanning heeft ook een filter op datum.

**Getest**

- De dagplanning toont per filter alleen de passende reserveringen: eigen locatie, alle locaties, Zuid, Noord + ochtend, Noord + middag, alle locaties + middag.
- Een reservering die alleen is aangevraagd, staat niet in de dagplanning.
- Een ongeldige datum geeft een foutmelding; een SQL-injectie in het filter doet niets.
- Medewerker Noord ziet de verzorging van Max (goedgekeurd op Noord), maar niet van Flappie (goedgekeurd op Zuid), niet van een dier dat alleen is aangevraagd en niet van een dier waarvan de reservering gisteren was.
- Medewerker Zuid ziet de verzorging van Flappie en niet van Max. Na afwijzen van de reservering vervalt de toegang.
- Een medewerker kan de verzorgingsgegevens niet aanpassen.
- Een eigenaar kan de dagplanning, de verzorgingspagina van de medewerker en de wijzigingslog niet openen.
- Opslaan zonder verandering geeft geen logregel; één veld veranderen geeft één logregel met de juiste oude en nieuwe waarde; twee velden geven twee logregels. Ongeldige invoer geeft geen logregel.
- In de wijzigingslog ziet medewerker Noord de inhoud bij Max en "Afgeschermd" bij Flappie; bij medewerker Zuid is dat andersom.
- Na het verwijderen van een dier blijft de logregel bestaan.
- HTML in een verzorgingsveld wordt in de wijzigingslog als gewone tekst getoond.
- De pagina's werken op computer en op telefoonbreedte.

**Eisen en planning:** FE-06, FE-07, FE-08, TE-04 en TE-05. Planningstaken T-14 en T-15.
