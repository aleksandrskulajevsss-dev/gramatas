
# Iedvesmas grāmata — administrācijas panelis

## Par projektu

Administrācijas panelis izdevniecībai "Iedvesmas grāmata".

Projekts izstrādāts prakses uzdevuma ietvaros, lai
izdevniecības darbinieki varētu pārvaldīt autoru
grāmatu pieteikumus un sekot līdzi grāmatu
izdošanas procesam.

Administrācijas panelis veidots, ievērojot
izdevniecības mājaslapas vizuālo stilu.

## Izmantotās tehnoloģijas

- PHP
- MySQL
- HTML5
- CSS3
- Apache
- Laragon
- HeidiSQL
- Git un GitHub

## Galvenās funkcijas

### Administratora autorizācija

- Pieslēgšanās ar lietotājvārdu un paroli.
- Paroļu droša glabāšana ar password_hash().
- Sesiju izmantošana autorizācijai.
- Izrakstīšanās no administrācijas paneļa.
- Neautorizētas piekļuves ierobežošana.

### Grāmatu pieteikumu pārvaldība

- Visu pieteikumu saraksts.
- Grāmatas nosaukuma un žanra apskate.
- Autora kontaktinformācijas apskate.
- Pieteikuma datuma apskate.
- Katra pieteikuma detalizētā lapa.

### Projekta statusu pārvaldība

Pieejamie statusi:

1. Saņemts
2. Izskatīšanā
3. Apstiprināts
4. Maketēšana
5. Druka
6. Pabeigts

Administrators var mainīt un saglabāt
katra grāmatas projekta statusu.

### Piezīmju pārvaldība

- Piezīmju pievienošana pieteikumiem.
- Piezīmju saglabāšana datubāzē.
- Piezīmju vēstures apskate.
- Piezīmes autora un datuma attēlošana.

### Meklēšana un filtrēšana

- Meklēšana pēc autora vārda.
- Meklēšana pēc grāmatas nosaukuma.
- Filtrēšana pēc projekta statusa.
- Filtrēšana pēc pieteikuma datuma.
- Filtru atiestatīšana.

### Manuskriptu pārvaldība

- PDF un DOCX failu augšupielāde.
- Maksimālais faila izmērs: 10 MB.
- Manuskripta piesaiste konkrētam pieteikumam.
- Manuskripta lejupielāde administratoram.
- Tiešās piekļuves ierobežošana manuskriptu mapei.

## Datubāzes struktūra

Projektā tiek izmantota MySQL datubāze
ar piecām tabulām:

- users — administratoru konti
- authors — autoru informācija
- applications — grāmatu pieteikumi
- statuses — projektu statusi
- notes — administratoru piezīmes

Datubāzes struktūra atrodama failā:

database/schema.sql

## Projekta instalēšana lokālajā vidē

### 1. Nepieciešamā programmatūra

- Laragon ar PHP, Apache un MySQL
- HeidiSQL vai cits MySQL pārvaldības rīks

### 2. Projekta iegūšana

Klonēt GitHub repozitoriju Laragon www mapē
ar mapes nosaukumu "gramatas".

### 3. Datubāzes izveidošana

HeidiSQL izveidot datubāzi:

gramatas

Iestatīt kodējumu:

utf8mb4_unicode_ci

Importēt datubāzes struktūru no:

database/schema.sql

### 4. Datubāzes savienojums

Izveidot failu:

config/database.php

Tajā konfigurēt PDO savienojumu ar
lokālo MySQL datubāzi.

Šis fails netiek augšupielādēts GitHub,
jo var saturēt piekļuves datus.

### 5. Administratora konts

Datubāzes users tabulā nepieciešams
izveidot administratora kontu.

Parole jāglabā kā password_hash()
ģenerēta vērtība, nevis atklāts teksts.

### 6. Projekta palaišana

Palaist Apache un MySQL Laragon vidē.

Pārlūkprogrammā atvērt:

http://localhost/gramatas/

Pieslēgties ar administratora kontu.

## Drošība

Projektā izmantoti šādi drošības risinājumi:

- PDO sagatavotie SQL vaicājumi.
- HTML izvades ekranēšana.
- Administratora sesiju pārbaude.
- CSRF aizsardzība datu maiņas formām.
- Paroļu glabāšana ar drošu hash.
- Augšupielādējamo failu tipu un izmēru pārbaude.
- Manuskriptu lejupielāde caur autorizētu PHP failu.
- Sensitīvo konfigurācijas failu izslēgšana no Git.

## Projekta ierobežojumi

- Projekts paredzēts lokālai demonstrēšanai.
- Vienam pieteikumam var pievienot vienu manuskriptu.
- Administratora konts sākotnēji jāizveido atsevišķi.
- Publiska autoru pieteikšanās forma nav iekļauta.
- Pirms publiskas izvietošanas nepieciešama
  papildu servera konfigurācijas un drošības pārbaude.

## Projekta mērķis

Izveidot vienkāršu, pārskatāmu un funkcionālu
administrācijas paneli, kas atvieglo
izdevniecības grāmatu projektu pārvaldību.
