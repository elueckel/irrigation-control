# Irrigation Control

Das Beregungsmodul für Symcon ermöglicht die Steuerung von Sprinklern. Hierfür können Sensordaten (Bodenfeuchte,Regen, etc.) und Wettervorhersagen genutzt werden um Ventile von Sprinklern zu bestimmten Zeit ein und aus zuschalten.  




Version 1.0
* 1 Gruppe mit 6 Abschnitten mit je 2 Ventilen
* 2 Masterventile welche den Abschnitten vorgeschaltet sind (Sicherheit und Druck)
* Steuerung der Einschaltzeit (z.B. um 23 Uhr, alle 3 Tage) und Laufzeit pro Abschnitt (bis zu 60 Minuten)
* Auslesen eines Bodensensors und Setzen von Werten wann der Boden feucht, am austrocknen und trocken ist
* Setzen von Werten für ausreichend Regen ... wieviel Regen muss fallen um einen austrocknenden Boden wieder ausreichend zu bewässern
* Unterbrechen der aktuellen Beregnung bei Regen und Wiederaufnahme wenn nicht genug Regen gefallen ist (es wird geprüft wieviel Regen in der letzten Stunde gefallen ist)
* Unterbrechen der Beregnung wenn die Wettervorhersage genug Regen innerhalb des Beregnungsabstands vorhersagt. Für die Vorhersage empfehle ich mein Wunderground Modul welches die Regenmenge für bis zu 5 Tage aufrechnet. 
* Benachrichtigung bei Start/Stop der Bewässerung
* Eintrag ins Log bei Start/Stop

Variablen zur Einbindung in ein Webfront/Mobiles Gerät
* Stop einer aktuellen Beregnung
* Manueller Start der Beregnung - 0 alle aktiven Abschnitte werden durchlaufen / Auswahl eines bestimmten Abschnitts start nur diesen
* Manuelle Zeit - Zeitvorgabe für die manuelle Beregnung (z.B. kann man einen Abschnitt so mal für 1 Minute testen) 

Watchdog
* Der Watchdog überprüft alle 10 Sekunden ob in die Ausführung eingegriffen werden muss. Den Eingriffen gehören automatische Ereignisse wie erkannter Regen oder manueller Start/Stop.