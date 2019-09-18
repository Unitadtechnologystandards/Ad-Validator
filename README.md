# README #

### About ###

Grundprinzip ist folgendermaßen:

Im Frontend laeuft eine Javascript/XHR/PHP Anwendung.

Der Benutzer gibt Parameter ein, diese werden via XHR an das Backend gesendet und dort in der PHP-Session aufbewahrt.

Es kann auch ein Zip-Archiv hochgeladen werden, dieses wird in einem temorären Arbeitsverzeichnis entpackt und der Verweis auf dieses Verzeichnis ebenfalls in der Session festgehalten.


#### Prüfvorgang ####

Startet der Benutzer den Prüfvorgang geschieht folgendes: 

- Es wird eine temporäre HTML-Seite im Arbeitsverzeichnis erzeugt, welche die zu testenden Eingaben enthält.
- Die Testseite beinhaltet auch das Javascript um das IAB_HOST_LOADED Signal an alle Werbemittel zu senden
- Die index.html eines Werbemittel-Uploads wird als Friendly-Iframe angesteuert
- Alternativ wird für ein eingegebenes Werbemittel eine Datei erzeugt und diese ebenso als Iframe aufgerufen.
- Das Iframe kann entweder "friendly" (same domain) oder "unfriendly" (andere domain) eingebunden werden 
- Weitere Assets werden als Script, Styles, Image direkt in die Testseite geschrieben. 
- Es wird dann ein Chrome-Browser gestartet und über den Debug-Kanal ferngesteuert
- Dazu wird ein Javascript auf dem Server (mittels node) ausgeführt, welches mit dem CRI (Chrome Remote Interface) eine Verbindung zu dem Browser aufnimmt. 
- Der Browser wird auf die Testseite gelenkt
- Jede Anfrage die von der Testseite ausgeht wird detailiert protokolliert
- Es wird ein Ergebnis-Objekt an das PHP-Backend übergeben
- Die Ergebnisse werde ausgewertet (zB Vendoren auflösen) und an das Frontend übergeben


#### Erkennung ReadyState ####

Im Chrome-Remote-Interface kann nur das Dom-Ready und das Load-Event gemessen werden.
Die Werbemittel reagieren jedoch auf das ReadyStateChange "complete" Event

In der Testseite wird ReadyStateChange "complete" UND Load-Event gemessen.
Das Delta in Mikrosekunden wird vom außen gemessenen Load-Event abgezogen.

So ist es möglich zu erkennen, ob Werbemittel in Friendly-Iframes tatsächlich erst mit dem Sub-Load Teil beginnen, wenn sie es auch dürfen.


#### Diagramme ####

Das Frontend ist jederzeit in der Lage ein Ergebnis-Objekt auszuwerten und grafisch aufzubereiten:

- Es werden 5 Tacho-Diagramme gezeichnet
- Es wird ein Aufruf-Liste mit Anmerkungen erstellt
- Es wird ein detailliertes Zeit/Aufruf Diagramm ("Profiling") gezeichnet
- Fehler, Probleme und Schwellwertüberschreitungen werden ebenfalls ausgewiesen
 
 
#### PDF Export ####

Der Benutzer kann nun einen PDF-Export veranlassen:

- Ein PHP-Backendprozess wird aufgerufen
- Der Prozess hat Zugriff auf das letzte Ergebnis
- Das Frontend-HTML wird geladen, das Ergebnis wird injiziert und das Ergebnis im temporären Arbeitsverzeichnis gespeichert
- Nun wird ein Chrome-Prozess mit PDF Speicherung auf die temporäre Seite gelenkt
- Die dabei entstehende Datei wird an den Benutzer ausgeliefert


#### Zurücksetzen ####

Die Funktion "Alle zurücksetzen" löscht das letzte Ergebnis, die Eingabe und auch die Arbeitsverzeichnisse.


### Setup ###

#### Umgebung ####

- php 7.2.19 bzw. PHP 7.3.8-1
- ext-json (php Erweiterung)
- ext-zip (php Erweiterung)
- Chromium 75.0.3770.100 bzw. Chromium 76.0.3809.100
- node v8.10.0 bzw. v10.15.2

Nur zur Installation:

- npm 3.5.2 bzw. 5.8.0


#### Zwei zertifizierte (Sub-)Domains ####

Beispielweise:

https://ovk-advalidator.de

und dazugehörig

https://unfriendly-ovk-advalidator.de



#### Installation ####

1. Repository clonen/exportieren
2. Schreibzugriff auf /path/to/public/workdir, /path/to/var/* ermöglichen
3. npm install 
4. gulp sass && gulp scripts && gulp fonts
5. Vhost zeigt auf /path/to/public 
6. /index.html als Standard-Seite im Vhost


#### (Headless) Chrome ####

Unter /usr/bin/chromium wird ein browser erwartet.

- Wird beim Messvorgang gestartet.

- Wird zur PDF Generierung benutzt.


#### Node ####

Unter /usr/bin/node wird ein Node erwartet, 
hiermit wird der laufende Chrome-Browser angesteuert. 


#### Frontend ####
	
Das Frontend basiert auf 

https://foundation.zurb.com/sites/docs/index.html

Das Frontend Javascript benutzt jquery

Für die Charts wird 

https://www.chartjs.org/

benutzt

	
~~~~
[/site]$ npm install
[/site]$ gulp sass
[/site]$ gulp scripts
[/site]$ gulp fonts
~~~~


#### Backend ####

Reines PHP (7.2) mit composer-Autoloader 


#### CRON ####

Reap old workdir files using:

find /path/to/public/workdir -type d -mmin +1800 -exec rm -rf {} +