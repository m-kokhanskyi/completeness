Das Modul „Completeness“ misst in Prozent den Ausfüllgrad von Pflichtfeldern, was erlaubt, die Qualität der Daten von Entitätseinträgen zu kontrollieren und zu verbessern. Zudem ermöglicht das Modul, die Entitätseinträge zu speichern, bei denen die Pflichtfelder leer geblieben sind. Bitte beachten Sie, dass, falls das Modul „Completeness“  in Ihrem System nicht installiert und aktiviert ist, es nicht möglich ist, die Einträge zu speichern, bei denen nicht alle Pflichtfelder ausgefüllt sind. 

Dank der [graphischen Darstellung](#completeness-dashlets) haben Sie die volle Kontrolle über die Vollständigkeit der Daten Ihrer Entitätseinträge im System.

## Installation 

Um das Modul „Completeness“ in Ihrem System zu installieren, gehen Sie auf `Administration > Modulmanager`; suchen Sie dieses Modul in der Liste „Shop“ und klicken Sie auf `Installieren`:

![Completeness install](_assets/completeness-install.jpg)

Wählen Sie im angezeigten Installations-Pop-up die gewünschte Version aus und klicken Sie auf den Button `Installieren`. Der Modulhintergrund wird grün und das Modul wird in den Bereich „Installiert“ des Modulmanagers verschoben. Klicken Sie `das Update starten`, um die Installation zu bestätigen.

Bitte beachten Sie, dass nach dem Systemupdate alle Nutzer abgemeldet werden. 

Um das Modul „Completeness“ zu aktualisieren / zu entfernen, nutzen Sie die entsprechenden Optionen aus dem Menü für einzelne Datensatzaktionen in `Administration > Modulmanager`.

## Funktionen für den Administrator

Das Modul „Completeness“ wurde in erster Linie für die Verwendung mit dem [TreoPIM](https://treopim.com/de/hilfe/what-is-treopim)-System erstellt, deshalb ist die weitergehende Beschreibung des Moduls im Kontext von TreoPIM angeführt.

### Aktivierung des Moduls 

Um den Mechanismus Completeness für eine bestimmte Entität zu aktivieren, gehen Sie auf `Administration > Entitätsmanager`, wählen die nötige Entität (z.B. Product) aus und klicken auf `Bearbeiten`: 

![Entity Mngr](_assets/entity-mngr.jpg)

Im angezeigten Bearbeitungs-Pop-up setzen Sie die Checkbox „Ausfüllen“ und klicken Sie auf den Button `Speichern`, um die Änderungen anzuwenden:

![Entity Editing](_assets/entity-editing.jpg)

Bitte beachten Sie, dass der Mechanismus Completeness für so viele Entitäten wie benötigt auf die gleiche Weise aktiviert werden kann.

### Die Felder und Attribute als Pflichtfelder und Pflichtattribute markieren 

Das Konzept der Vollständigkeit gilt nur für die Pflichtfelder. Um ein Feld als Pflichtfeld zu markieren, gehen Sie auf `Administration > Entitätsmanager`, klicken auf `Felder` bei der nötigen Entität: 

![Entity Mngr fields](_assets/entity-mngr-fields.jpg)

Im angezeigten Fenster werden alle Felder der ausgewählten Entität angezeigt. Wählen Sie das nötige Feld aus oder erstellen Sie ein neues, das ausgefüllt werden muss und setzen Sie die Checkbox `Erforderlich`.

![Required Field](_assets/required-field.jpg)

Sie können auch die *Produktattribute* (sowohl von `Global` und `Channel` Scope) als Pflichtattribute definieren. Dies kann auf der Seite der Detailansicht von Produktfamilie durchgeführt werden.

![Required attributes](_assets/required-attributes.jpg)

Die Zuordnung von kanalspezifischen Attributen zu Produkten ermöglicht die Berechnung der *kanalspezifischen Vollständigkeit* zusätzlich zu der gesamten Vollständigkeit. Wenn es jedoch keine Pflichtattribute gibt, die dem angegebenen Kanal zugeordnet sind, wird die Vollständigkeit aufgrund der `globalen` Pflichtattributwerte berechnet. 

Weitere Informationen zu den [Attributen](https://treopim.com/de/hilfe/attributes), [Kanälen](https://treopim.com/de/hilfe/channels) und [Produktfamilien](https://treopim.com/de/hilfe/product-families) finden Sie im **TreoPIM User Guide**.

### Konfiguration der Vollständigkeitswert-Anzeige 

Um die Anzeige des Ausfüllgrades für die vorher konfigurierte Entität hinzuzufügen, gehen Sie auf `Administration > Layout Manager` und klicken Sie auf die bestimmte Entität in der Liste, um die Liste der Layouts auszuklappen, die für diese Entität verfügbar sind. Klicken Sie auf das Layout, das Sie konfigurieren möchten (z.B. `Liste`) und aktivieren Sie das Feld `Complete` durch drag-and-drop von der rechten Spalte in die linke:    

![Layout Mngr complete](_assets/layout-mngr-complete.jpg)

Bitte beachten Sie, dass die gesamte Vollständigkeit für die Datenfelder der Standardsprache berechnet wird. Wenn Sie jedoch möchten, dass Ihre Felddaten in mehreren Sprachen vorhanden sind und dabei die Möglichkeit benötigt wird, dass der Ausfüllgrad berechnet und angezeigt wird, können Sie das Modul [**Multi-Languages**](https://treopim.com/de/shop/multi-languages) in Ihrem System installieren und korrekt konfigurieren. Anschließend [fügen](#die-felder-und-attributte-als-pflichtfelder-und-pflichtatribute-markieren) Sie die Felder `Complete (<Local>)` (z.B. `Complete (EnUs)`, `Complete (DeDe)` usw.) für die gewünschten Entitäten auf die oben beschriebene Weise hinzu.

Klicken Sie auf den Button `Speichern`, um den Vorgang abzuschließen. Das hinzugefügte Feld `Complete` wird im konfigurierten Layouttyp für die gegebene Entität angezeigt:

![Completeness added](_assets/complete-added.jpg)

Bei der Bearbeitung des Entitätseintrags mit der aktivierten Vollständigkeit (z.B. beim Hinzufügen, Entfernen usw. des Pflichtfeldes) wird der Prozentwert der Vollständigkeit sofort neu berechnet.

Beachten Sie bitte, dass Sie das Produkt nicht aktivieren können (d.h. die Checkbox `Aktiv` setzen), wenn die gesamte Vollständigkeit nicht 100% beträgt: 

![Activation error](_assets/activation-error.jpg)

## Funktionen für den Nutzer 

Nachdem das Modul „Completeness“ vom Administrator installiert und konfiguriert wurde, kann der Nutzer den vom Administrator vordefinierten Ausfüllgrad der Entitätseinträge ansehen und die Einträge in dieser Spalte entsprechend sortieren:

![Completeness Sorting](_assets/complete-sorting.jpg)

Der Nutzer kann auch die Vollständigkeitsstatistik beeinflussen, indem er die Pflichtfelder und Attribute der konfigurierten Entitäten gemäß seinen Zugangsrechten bearbeitet.

### Logik der Vollständigkeitsberechnung 

TreoPIM unterstützt drei Typen der Berechnung von Vollständigkeit der Entitätsdaten in Prozent:

- **Overall** – Ausfüllgrad der Pflichtfelder und `globalen` Pflichtattribute; 
- **Channel** – Ausfüllgrad der Pflichtfelder und `kanalspezifischen` Pflichtattribute. Wenn `kanalspezifische` Pflichtattribute nicht verfügbar sind, wird die Vollständigkeit mithilfe der `globalen` Pflichtattributwerte berechnet. 
- **Locale** – Ausfüllgrad der mehrsprachigen Pflichtfelder und mehrsprachigen `globalen` Pflichtattribute (`Array MultiLang`, `Enum MultiLang`, `Multi-Enum MultiLang`, `Text MultiLang`, `Varchar MultiLang`, und `Wysiwyg MultiLang`). Weitere Informationen finden Sie in der Beschreibung des Moduls [**Multi-Languages**](https://treopim.com/de/shop/multi-languages).   

### Vollständigkeit-Dashlets  

Um die Vollständigkeit der Ausfüllung von Produktinformationen bequem zu verfolgen, kann der Nutzer spezielle Dashlets auf seinem benutzerdefinierten Dashboard anzeigen lassen:

![Completeness Dashlets](_assets/completeness-dashlets.jpg)

Die folgenden Dashlets können angezeigt werden: 
- **Vollständigkeitsübersicht** – Werte der gesamten Vollständigkeit, einschließlich konfigurierter Locales und Kanäle, in der Tabellenansicht. 
- **Vollständigkeit der Locale** – Vollständigkeit nach Locales getrennt und insgesamt, in grafischer Form.
- **Vollständigkeit der Kanäle** – Vollständigkeit nach Kanälen getrennt und insgesamt, in grafischer Form. 

Weitere Informationen zu Dashboards und Dashlets finden Sie im entsprechenden [Artikel](https://treopim.com/de/hilfe/user-interface) im TreoPIM User Guide.

***Erwerben Sie das Modul "Completeness" jetzt, um die Qualität ihrer Produktdaten zu kontrollieren und wesentlich zu verbessern!***


