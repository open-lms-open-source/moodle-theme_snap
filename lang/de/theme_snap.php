<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  Copyright (c) 2023 Open LMS (https://www.openlms.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aboutcourse'] = 'Informationen zu diesem Kurs';
$string['activity'] = 'Aktivität';
$string['action:changeassetvisibility'] = 'Sichtbarkeit des Objekts ändern';
$string['action:duplicateasset'] = 'Objekt duplizieren';
$string['action:changesectionvisibility'] = 'Sichtbarkeit des Abschnitts ändern';
$string['action:highlightsectionvisibility'] = 'Sichtbarkeit des Abschnitts hervorheben';
$string['action:sectiontoc'] = 'Inhaltsverzeichnis für Abschnitt abrufen';
$string['addanewsection'] = 'Neuen Abschnitt erstellen';
$string['addresourceoractivity'] = 'Lernaktivität erstellen';
$string['admin'] = 'Administrator';
$string['advancedbrandingheading'] = 'Erweitertes Branding';
$string['ago'] = 'vor';
$string['answered'] = 'Beantwortet';
$string['appendices'] = 'Werkzeuge';
$string['arialabelnewsarticle'] = 'Nachrichtenartikel';
$string['assigndraft'] = 'Entwurf erfordert Ihre Bestätigung';
$string['assignreopened'] = 'Erneut geöffnet';
$string['at'] = 'um';
$string['attempted'] = 'Versucht';
$string['basics'] = 'Grundlagen';
$string['brandingheading'] = 'Branding';
$string['browse'] = 'Durchsuchen';
$string['browseallcourses'] = 'Alle Kurse durchsuchen';
$string['cachedef_activity_deadlines'] = 'Cache-Speicher für Aktivitätstermine eines Nutzers/einer Nutzerin.';
$string['cachedef_generalstaticappcache'] = 'Allgemeiner statischer Snap-Cache auf Anwendungsebene';
$string['cachedef_course_completion_progress'] = 'Damit werden Abschlussdaten pro Kurs oder Nutzer/in zwischengespeichert.';
$string['cachedef_course_completion_progress_ts'] = 'Damit werden Zwischenspeicherungen auf Sitzungsebene ungültig gemacht, wenn sich die Abschlusseinstellungen für einen Kurs oder ein Modul ändern.';
$string['cachedef_webservicedefinitions'] = 'Zwischenspeichern von automatisch erzeugten Webservice-Definitionen.';
$string['card'] = 'Karte';
$string['categoryedit'] = 'Kategorie bearbeiten';
$string['category_color'] = 'Kategoriefarbe';
$string['category_color_description'] = 'Farbe der Kurskategorie. Untergeordnete Kurse übernehmen die Konfiguration der nächstliegenden übergeordneten Kategorie.';
$string['category_color_palette'] = 'Farbpalette';
$string['category_color_palette_description'] = 'Siehe den entsprechenden Hexadezimalwert für die angegebene Farbe. Dies
wirkt sich nicht auf jede Konfiguration aus. Es ist nur ein Beispiel, um Nutzer/innen beim Erstellen des Konfigurationswerts zu unterstützen.';
$string['changecoverimage'] = 'Titelbild ändern';
$string['changefullname'] = 'Namen der Website bearbeiten';
$string['chapters'] = 'Kapitel';
$string['choosereadme'] = '<div class="clearfix"><div class="theme_screenshot"><h2>Snap</h2><img class=img-polaroid src="snap/pix/screenshot.jpg" /></div></div>';
$string['close'] = 'Schließen';
$string['conditional'] = 'Bedingt';
$string['contents'] = 'Inhalte';
$string['contributed'] = 'Mitgewirkt';
$string['courses'] = 'Kurse';
$string['coursecontacts'] = 'Kurskontakte';
$string['coursedisplay'] = 'Kursanzeige';
$string['coursefootertoggle'] = 'Kursfußzeile';
$string['coursefootertoggledesc'] = 'In der Fußzeile auf einer Kursseite werden nützliche Informationen für Nutzer/innen angezeigt, einschließlich Kurskontakte, Kursbeschreibung und aktuelle Aktivitäten im Kurs.';
$string['courseformatnotification'] = 'Das momentan von Ihnen genutzte Kursformat wird nicht vollständig vom Snap-Thema unterstützt. Für eine bestmögliche Erfahrung empfiehlt Open LMS die Kursformate &quot;Themen&quot; oder &quot;Wöchentlich&quot; mit dem Snap-Thema. Das Kursformat kann unter <a href="{$a}">Kurseinstellungen</a> geändert werden.';
$string['coursefixydefaulttext'] = 'Sie sind in keinem Kurs eingeschrieben.<br>Hier werden Kurse angezeigt, für die Sie angemeldet sind.';
$string['coursegrade'] = 'Kursbewertung:';
$string['coursepartialrender'] = '&quot;Lazy Loading&quot; für Kursabschnitte aktivieren';
$string['coursepartialrenderdesc'] = 'Wenn diese Option aktiviert ist, werden Kursabschnitte auf Anforderung geladen, wenn sie von einem Nutzer ausgewählt werden. So können Kurse mit umfangreichem Inhalt schneller geladen werden.';
$string['coursenavigation'] = 'Kursnavigation';
$string['coursesummaryfilesunsuitable'] = 'Bitte leeren Sie Ihre Kursbeschreibungsdateien vor dem Ändern des Titelbilds';
$string['courseactivitieslabel'] = 'Abschnittsaktivitäten';
$string['coursetools'] = 'Kurs-Dashboard';
$string['coverdisplay'] = 'Titelanzeige';
$string['covercarousel'] = 'Titelkarussell';
$string['covercarouselon'] = 'Titelkarussell verwenden';
$string['covercarouseldescription'] = '<p>Das Karussell besteht aus einem Satz rotierender Banner oder einer Diashow, die auf der Startseite Ihrer Website statt auf dem Titelbild angezeigt wird.</p>
<p>Fügen Sie bis zu 3 Bilder, einen Titel für jede Folie und einen optionalen Untertitel hinzu. Bilder mit 1200 x 600 Pixeln sind am besten geeignet.</p>';
$string['covercarouselsronly'] = 'Dies ist ein Karussell mit automatisch rotierenden Objektträgern. Aktivieren Sie eine der Schaltflächen, um die Drehung zu deaktivieren. Verwenden Sie die Schaltflächen &quot;Weiter&quot; und &quot;Zurück&quot;, um zu navigieren, oder springen Sie mithilfe der Folienpunkte zu einer Folie.';
$string['covercarouselplaybutton'] = 'Fahren Sie mit der automatischen Drehung der Folien für das Karussell fort.';
$string['covercarouselpausebutton'] = 'Halten Sie die automatische Drehung der Folien für das Karussell an.';
$string['coverimage'] = 'Titelbild';
$string['covervideo'] = 'Titelvideo';
$string['comingsoon'] = 'Demnächst!';
$string['createsection'] = 'Abschnitte erstellen';
$string['current'] = 'Aktuell';
$string['customcss'] = 'CSS-Anpassungen';
$string['customcssdesc'] = 'Bitte berücksichtigen Sie, dass größere Befugnis auch größere Verantwortlichkeit mit sich bringt. Die Behebung aller Probleme, die auf das hier hinzugefügte CSS zurückzuführen sind, liegt in Ihrer Verantwortung. Der Support von Open LMS behebt keine Probleme im Zusammenhang mit CSS-Inhalten und stellt dafür auch keine Hilfe bereit.';
$string['customtopbar'] = 'Navigationsleiste';
$string['customisenavbar'] = 'Farben der Navigationsleiste ändern';
$string['customisenavbutton'] = 'Farben der Schaltfläche &quot;Meine Kurse&quot; ändern';
$string['customisecustommenu'] = 'Textfarbe des benutzerdefinierten Menüs ändern';
$string['custommenutext'] = 'Textfarbe des benutzerdefinierten Menüs';
$string['deadlines'] = 'Termine';
$string['deadlinestoggle'] = 'Termine';
$string['deadlinestoggledesc'] = 'Anstehende Termine für Aktivitäten in belegten Kursen für alle Nutzer/innen anzeigen.';
$string['defaultsummary'] = 'In diesem Bereich können Sie erläutern, um was es bei dem Thema geht – mit Text, Bildern, Audio und Video.';
$string['defaultintrosummary'] = 'Willkommen zu Ihrem neuen Kurs {$a}.
<br>Geben Sie zuerst eine Kurserläuterung an. Hierfür können Sie Text, Bilder, Audio und Video verwenden.';
$string['defaulttopictitle'] = 'Thema ohne Titel';
$string['debugerrors'] = 'Debugging-Fehler';
$string['deleteassetconfirm'] = '{$a} löschen';
$string['deletingasset'] = '{$a} wird gelöscht';
$string['deletingassetname'] = '{$a->type} &quot;{$a->name}&quot; wird gelöscht';
$string['deletesectionconfirm'] = 'Abschnitt löschen';
$string['deletingsection'] = 'Abschnitt &quot;{$a}&quot; wird gelöscht';
$string['draft'] = 'Für Teilnehmer nicht veröffentlicht';
$string['dropzonelabel'] = 'Dateien zum Anhängen ablegen oder <span class="fake-link">suchen</span>';
$string['due'] = 'Fällig {$a}';
$string['edit'] = '&quot;{$a}&quot; bearbeiten';
$string['editcoursecontent'] = 'Blöcke bearbeiten';
$string['editcoursesettings'] = 'Kurseinstellungen';
$string['editcoursetopic'] = 'Sitzung bearbeiten';
$string['editcustomfooter'] = 'Fußzeile bearbeiten';
$string['editcustommenu'] = 'Benutzerdefiniertes Menü bearbeiten';
$string['error'] = 'Fehler';
$string['errorgettingfeed'] = 'Beim Abrufen der Feedelemente ist ein Fehler aufgetreten.';
$string['error:categorycolorinvalidjson'] = 'Falsches JSON-Format für Kurskategorien';
$string['error:categorycolorinvalidvalue'] = 'Die Datensatz-ID oder der Farbwert für Kategory „{$a}“ ist ungültig.';
$string['error:categorynotfound'] = 'Das Kategoriedatensatz mit der ID „{$a}“ wurde nicht gefunden.';
$string['error:coverimageexceedsmaxbytes'] = 'Titelbild überschreitet die auf Website-Ebene maximal zulässige Dateigröße ({$a})';
$string['error:coverimageresolutionlow'] = 'Die besten Ergebnisse erzielen Sie mit einem größeren Bild mit einer Breite von mindestens 1.024 Pixel.';
$string['error:duplicatedcategoryids'] = 'Falsches JSON-Format, einige IDs sind doppelt';
$string['error:failedtochangeassetvisibility'] = 'Objekt kann nicht verborgen/angezeigt werden';
$string['error:failedtochangesectionvisibility'] = 'Abschnitt kann nicht verborgen/angezeigt werden';
$string['error:failedtohighlightsection'] = 'Abschnitt kann nicht hervorgehoben werden';
$string['error:failedtoduplicateasset'] = 'Fehler beim Duplizieren';
$string['error:failedtodeleteasset'] = 'Fehler beim Löschen des Objekts';
$string['error:failedtotoc'] = 'Inhaltsverzeichnis konnte nicht abgerufen werden';
$string['extension'] = 'Erweiterung {$a}';
$string['facebook'] = 'Facebook';
$string['facebookdesc'] = 'Die URL Ihrer Facebook-Seite.';
$string['favicon'] = 'Favicon';
$string['favicondesc'] = 'Favicons erscheinen in der Adressleiste Ihres Browsers, in den Lesezeichen von Nutzer/innen und in Verknüpfungen auf mobilen Geräten.';
$string['favorite'] = 'Favorit-{$a}';
$string['favorited'] = 'Favorisiert: {$a}';
$string['featurespots'] = 'Funktions-Spots';
$string['featurespotsedit'] = 'Funktions-Spots bearbeiten';
$string['featurespotshelp'] = '<p>Fügen Sie bis zu 3 Feature-Spots zur Startseite Ihrer Website hinzu, um die wichtigsten Vorteile für aktuelle und potenzielle Nutzer/innen hervorzuheben.</p>
<p>Fügen Sie für jede Funktion einen Titel, einen Inhalt und ein optionales Bild hinzu. Die Bilder sollten quadratisch und nicht größer als 200px x 200px sein.</p>';
$string['featurespotsheading'] = 'Überschrift für Funktions-Spots';
$string['featureonetitle'] = 'Funktion 1 – Titel';
$string['featuretwotitle'] = 'Funktion 2 – Titel';
$string['featurethreetitle'] = 'Funktion 3 – Titel';
$string['featureonetitlelink'] = 'Link für Funktion 1 – Titel';
$string['featuretwotitlelink'] = 'Link für Funktion 2 – Titel';
$string['featurethreetitlelink'] = 'Link für Funktion 3 – Titel';
$string['featuretitlelinkdesc'] = 'Geben Sie die URL ein, mit der dieser Funktions-Spot verknüpft werden soll. Sie können externe oder interne Links innerhalb Ihrer Website hinzufügen. Zum Hinzufügen eines internen Links kopieren Sie diesen von der URL ab dem „/“. Beispiel: Bei einem Link zu einem Kurs wäre dies „/course/view.php?id=160“. Zum Hinzufügen eines externen Links beginnen Sie diesen mit „https://“.';
$string['featureonetitlecb'] = 'Funktion 1 in einem neuen Fenster öffnen';
$string['featuretwotitlecb'] = 'Funktion 2 in einem neuen Fenster öffnen';
$string['featurethreetitlecb'] = 'Funktion 3 in einem neuen Fenster öffnen';
$string['featuretitlecbdesc'] = 'Wenn diese Option aktiviert ist, wird der zum Funktions-Spot hinzugefügte Link in einem neuen Fenster geöffnet';
$string['featureonetext'] = 'Funktion 1 – Inhalt';
$string['featuretwotext'] = 'Funktion 2 – Inhalt';
$string['featurethreetext'] = 'Funktion 3 – Inhalt';
$string['featureoneimage'] = 'Funktion 1 – Bild';
$string['featuretwoimage'] = 'Funktion 2 – Bild';
$string['featurethreeimage'] = 'Funktion 3 – Bild';
$string['featuredcourses'] = 'Ausgewählte Kurse';
$string['featuredcourseshelp'] = 'Heben Sie bis zu 8 ausgewählte Kurse für die Startseite Ihrer Website hervor. Geben Sie die Kurs-ID ein, um einen Kurs zu kennzeichnen.';
$string['featuredcoursesheading'] = 'Überschrift für ausgewählte Kurse';
$string['featuredcourseone'] = 'Ausgewählter Kurs 1';
$string['featuredcoursetwo'] = 'Ausgewählter Kurs 2';
$string['featuredcoursethree'] = 'Ausgewählter Kurs 3';
$string['featuredcoursefour'] = 'Ausgewählter Kurs 4';
$string['featuredcoursefive'] = 'Ausgewählter Kurs 5';
$string['featuredcoursesix'] = 'Ausgewählter Kurs 6';
$string['featuredcourseseven'] = 'Ausgewählter Kurs 7';
$string['featuredcourseeight'] = 'Ausgewählter Kurs 8';
$string['featuredcoursesedit'] = 'Ausgewählte Kurse bearbeiten';
$string['featuredcoursesbrowseall'] = 'Alle Kurse durchsuchen';
$string['featuredcoursesbrowsealldesc'] = 'Link &quot;Alle Kurse durchsuchen&quot; hinzufügen';
$string['feedbackavailable'] = 'Feedback verfügbar';
$string['feedbacktoggle'] = 'Feedback und Bewertung';
$string['feedbacktoggledesc'] = 'Lernenden aktuelles Feedback und Lehrenden aktuelle, noch zu bewertende Aufgabenabgaben anzeigen.';
$string['footnote'] = 'Fußzeile der Website';
$string['footnotedesc'] = 'Eine Fußzeile für Ihre Website. Dieser Bereich eignet sich besonders gut für Links zu Hilfe, Support und weiteren Websites, über die Ihr Unternehmen verfügt und auf die Sie die Lernenden/Lehrenden aufmerksam machen möchten, also z. B. Bibliothek und E-Mail-Adresse.';
$string['forcepwdwarningpersonalmenu'] = 'Sie müssen <a href="{$a}">Ihr Kennwort ändern</a>, bevor Sie das Menü &quot;Persönlich&quot; verwenden können.';
$string['forumauthor'] = 'Autor/in';
$string['forumlastpost'] = 'Letzter Eintrag';
$string['forumpicturegroup'] = 'Gruppe';
$string['forumreplies'] = 'Antworten';
$string['forumtopic'] = 'Thema';
$string['forumposts'] = 'Forumseinträge';
$string['forumpoststoggle'] = 'Forumseinträge';
$string['forumpoststoggledesc'] = 'Für Nutzer/innen die 10 aktuellsten Forumseinträge aus ihren Kursen anzeigen.';
$string['fullname'] = 'Name der Website';
$string['fullnamedesc'] = 'Der Name Ihrer Website.';
$string['graderadviseuserreport'] = 'Der „Bewerterbericht“ funktioniert auf mobilen Geräten nur eingeschränkt. Hier wird stattdessen der „Benutzerbericht“ empfohlen.';
$string['grading'] = 'Wird bewertet';
$string['help'] = 'Hilfe';
$string['helpguide'] = 'Hilfe-Leitfaden';
$string['headingfont'] = 'Schriftart für die Überschrift';
$string['headingfont_desc'] = 'Diese Schriftart (Sans Serif) wird in den Überschriften (Elemente h1–h6) auf Ihrer Website verwendet. Wenn Sie eine nutzer/innendefinierte Webfont einfügen, denken Sie daran, diese dem Moodle Extra-HTML-Formular hinzuzufügen. Wenn Sie Schriftarten anderer Elemente ändern möchten, nutzen Sie bitte die Option &quot;CSS-Anpassungen&quot;. Beispiele dazu finden Sie in dieser <a href="https://help.openlms.net/en/administrator/manage-a-site/snap-font-family-with-custom-css/" target="_blank">Dokumentation</a>.';
$string['helpwithlogin'] = 'Hilfe beim Anmelden';
$string['helpwithloginandguest'] = 'Hilfe beim Anmelden/Gästezugriff';
$string['hiddencoursestoggle'] = 'Ausgeblendete Kurse';
$string['highlightedsection'] = 'markiert';
$string['home'] = 'Startseite';
$string['image'] = 'Bild';
$string['images'] = 'Bilder';
$string['instagram'] = 'Instagram';
$string['instagramdesc'] = 'Die URL Ihres Instagram-Kontos.';
$string['introduction'] = 'Einführung';
$string['jsontext'] = 'JSON-Text';
$string['jsontextdescription'] = 'Der Textbereich validiert die angegebene JSON, sodass nur vorhandene Kategorien zulässig sind,
nur numerische Werte als ID-Datensätze (Kategoriedatensätze) gültig sind und nur hexadezimale Werte als Farben akzeptiert werden.
Hier ein Beispiel:<br>
{&quot;1&quot;:&quot;#FAAFFF&quot;,<br>
&quot;45&quot;:&quot;#AFF&quot;,<br>
&quot;65&quot;:&quot;#FFF228&quot;,<br>
&quot;12&quot;:&quot;#CC0084&quot;,<br>
&quot;56&quot;:&quot;#CC0087&quot;,<br>
&quot;89&quot;:&quot;#CCF084&quot;}';
$string['knowledgebase'] = 'Open LMS Knowledge-Base';
$string['list'] = 'Liste';
$string['linkedin'] = 'LinkedIn';
$string['linkedindesc'] = 'Die LinkedIn-URL Ihres Unternehmens.';
$string['leftnav'] = 'Inhaltsverzeichnis';
$string['leftnavdesc'] = 'Wählen Sie aus, wo das Inhaltsverzeichnis angezeigt werden soll. Listen können mehr Inhalte aufnehmen und sind für Kurse mit vielen Themen gut geeignet.';
$string['loading'] = 'Wird geladen...';
$string['loggedinasguest'] = 'Sie sind als Gast angemeldet.';
$string['loggedoutmsg'] = 'Sie sind aktuell abgemeldet. Wenn Sie diese Website weiterhin nutzen möchten, melden Sie sich bitte wieder an.';
$string['loggedoutmsgtitle'] = 'Sie sind abgemeldet';
$string['loggedoutfailmsg'] = 'Sie müssen bei {$a} angemeldet sein.';
$string['loginform'] = 'Anmelden';
$string['logo'] = 'Logo';
$string['logodesc'] = 'Ihr Logo wird im Kopfbereich auf der gesamten Website angezeigt.';
$string['menu'] = 'Meine Kurse';
$string['messageread'] = 'Mitteilungstext';
$string['messages'] = 'Mitteilungen';
$string['messagestoggle'] = 'Mitteilungen';
$string['messagestoggledesc'] = 'Für Nutzer/innen die neuesten, in den letzten 12 Wochen erhaltenen Mitteilungen anzeigen.';
$string['more'] = 'More';
$string['morenews'] = 'Mehr Nachrichten';
$string['movingstartedhelp'] = 'Navigiert zu dem Ort, an dem Abschnitt &quot;{$a}&quot; platziert werden soll';
$string['movingdropsectionhelp'] = 'Platziert Abschnitt &quot;{$a->moving}&quot; vor Abschnitt &quot;{$a->before}&quot;';
$string['moving'] = '&quot;{$a}&quot; wird verschoben';
$string['movingcount'] = '{$a} Objekte werden verschoben';
$string['movefailed'] = '&quot;{$a}&quot; konnte nicht verschoben werden';
$string['move'] = '&quot;{$a}&quot; verschieben';
$string['movehere'] = 'Hierhin verschieben';
$string['movesection'] = 'Abschnitt verschieben';
$string['navbarbg'] = 'Hintergrundfarbe';
$string['navbarlink'] = 'Textfarbe';
$string['navbarbuttoncolor'] = 'Hintergrundfarbe';
$string['navbarbuttonlink'] = 'Textfarbe';
$string['nextsection'] = 'Nächster Bereich';
$string['nodeadlines'] = 'Sie haben keine anstehenden Termine.';
$string['noforumposts'] = 'Es liegen keine relevanten Forumseinträge vor.';
$string['nograded'] = 'Sie haben kein aktuelles Feedback.';
$string['nograding'] = 'Sie haben keine abgegebenen Aufgaben, die bewertet werden müssen.';
$string['nomessages'] = 'Sie haben keine Mitteilungen.';
$string['notanswered'] = 'Nicht beantwortet';
$string['notattempted'] = 'Nicht versucht';
$string['notcontributed'] = 'Nicht mitgewirkt';
$string['notpublished'] = 'Für Teilnehmer nicht veröffentlicht';
$string['notsubmitted'] = 'Nicht abgegeben';
$string['overdue'] = 'Überfällig';
$string['personalmenu'] = 'Menü &quot;Persönlich&quot;';
$string['personalmenufeatures'] = 'Funktionen im Menü &quot;Persönlich&quot;';
$string['personalmenulogintoggle'] = 'Persönliches Menü bei Anmeldung anzeigen';
$string['personalmenulogintoggledesc'] = 'Öffnet das Persönliche Menü direkt nach der Anmeldung';
$string['personalmenuadvancedfeedsenable'] = 'Erweiterte Feeds aktivieren';
$string['personalmenuadvancedfeedsenabledesc'] = 'Erweiterte Feeds laden persönliche Menüelemente, sodass die Ladezeiten verkürzt und Inhalte auf Anforderung aktualisiert werden können.';
$string['personalmenuadvancedfeedsperpage'] = 'Erweiterte Feeds – Anzahl der angezeigten Elemente';
$string['personalmenuadvancedfeedsperpagedesc'] = 'Wählen Sie die Anzahl der Elemente aus, die in dem Feed angezeigt werden sollen. Mit der Option <strong>Mehr anzeigen</strong> können Nutzer/innen weitere Elemente anzeigen.';
$string['personalmenuadvancedfeedslifetime'] = 'Aufbewahrungsdauer für erweiterte Feeds';
$string['personalmenuadvancedfeedslifetimedesc'] = 'Wählen Sie die Dauer aus, die Feeds im Browser nach Anmeldung zwischengespeichert werden sollen. Wenn Sie den Wert auf „0“ festlegen, werden die Feeds nicht im Browser zwischengespeichert.';
$string['personalmenurefreshdeadlines'] = 'Aktualisieren Sie Termine mithilfe einer geplanten Aufgabe.';
$string['personalmenurefreshdeadlinesdesc'] = 'Wenn die Aufgabe ausgeführt wird, werden die Termindaten aktualisiert, um schnellere Seitenladezeiten zu ermöglichen.';
$string['pld'] = 'PLD';
$string['pluginname'] = 'Snap';
$string['poster'] = 'Titelbild';
$string['posterdesc'] = 'Ein großes Bild im Kopfbereich der ersten Seite Ihre Website. Bilder im Querformat (1.200 x 600 Pixel) oder größer eignen sich am besten.';
$string['poweredbyrunby'] = 'Entwickelt mit <a href="https://{$a->subdomain}.openlms.net/" target="_blank" rel="noopener">Open LMS</a>,
Ein <a href="https://moodle.com/" target="_blank" rel="noopener">Produkt</a> auf Moodle-Basis.<br>
Copyright © {$a->year} Open LMS, Alle Rechte vorbehalten.';
$string['previoussection'] = 'Vorheriger Abschnitt';
$string['privacy:metadata:theme_snap_course_favorites:courseid'] = 'Die Kurs-ID des Kurses, den der/die Nutzer/in bevorzugt hat';
$string['privacy:metadata:theme_snap_course_favorites:userid'] = 'Die Nutzer-ID des Nutzers/der Nutzerin, der/die den Kurs bevorzugt hat';
$string['privacy:metadata:theme_snap_course_favorites:timefavorited'] = 'Der Zeitstempel des Zeitpunkts, zu dem der/die Nutzer/in den Kurs bevorzugt hat';
$string['privacy:metadata:theme_snap_course_favorites'] = 'Speichert die Kursfavoriten der Nutzer/innen für Snap';
$string['problemsfound'] = 'Probleme gefunden';
$string['progress'] = 'Fortschritt';
$string['readmore'] = 'Mehr lesen »';
$string['recentactivity'] = 'Letzte Aktivität';
$string['recentfeedback'] = 'Feedback';
$string['region-main'] = 'Primär';
$string['region-side-main-box'] = 'Primär';
$string['region-side-post'] = 'Rechts';
$string['region-side-pre'] = 'Links';
$string['region-side-top'] = 'Oben';
$string['released'] = 'Version: {$a}';
$string['reopened'] = 'Erneut geöffnet';
$string['resourcedisplay'] = 'Anzeige von Ressourcen';
$string['resourcedisplayhelp'] = 'Wählen Sie aus, welche Anhänge und Links in Ihrem Kurs erscheinen sollen. Das Snap-Design unterstützt keine Multimedia-Dateien in der Beschreibung der Kurzaktivitäts- und Ressourcenkarten.';
$string['displaydescription'] = 'Beschreibung anzeigen';
$string['displaydescriptionhelp'] = 'Wählen Sie aus, dass erst eine Beschreibung der Ressourcen- und URL-Aktivitäten auf einer neuen Seite angezeigt werden. Die Teilnehmer greifen über die Beschreibung auf Inhalte zu.';
$string['search'] = 'Inhalte suchen';
$string['showcoursegradepersonalmenu'] = 'Bewertungen';
$string['showcoursegradepersonalmenudesc'] = 'Nutzer/innen ihre Bewertung auf Kurskarten im Menü &quot;Persönlich&quot; anzeigen';
$string['socialmedia'] = 'Soziale Medien';
$string['submitted'] = 'Abgegeben';
$string['sitedescription'] = 'Website-Beschreibung';
$string['subtitle'] = 'Untertitel';
$string['subtitle_desc'] = 'Kurze Beschreibung Ihrer Website für Nutzer/innen.';
$string['summarylabel'] = 'Zusammenfassung des Abschnitts';
$string['themecolor'] = 'Website-Farbe';
$string['themecolordesc'] = 'Helle Farben eignen sich am besten und verleihen Ihrer Website ein modernes Aussehen.';
$string['title'] = 'Titel';
$string['top'] = 'Oben';
$string['topbarbgcolor'] = 'Farbe für Navigationsleiste';
$string['topbarlinkcolor'] = 'Link- und Symbolfarbe für Navigationsleiste';
$string['topbarbuttoncolor'] = 'Hintergrund für &quot;Meine Kurse&quot;';
$string['togglenavigation'] = 'Navigation ein-/ausblenden';
$string['topicactions'] = 'Themenaktionen';
$string['twitter'] = 'Twitter';
$string['twitterdesc'] = 'Die URL Ihres Twitter-Kontos.';
$string['unenrolme'] = 'Mich abmelden';
$string['enrolme'] = 'Ich möchte mich anmelden';
$string['unread'] = 'ungelesen';
$string['unsupportedcoverimagetype'] = 'Nicht unterstützter Titelbildtyp ({$a})';
$string['via'] = 'via';
$string['viewcourse'] = 'Kurs anzeigen';
$string['viewmore'] = 'Mehr anzeigen';
$string['viewyourprofile'] = 'Eigenes Profil anzeigen';
$string['viewmyfeedback'] = 'Feedback anzeigen';
$string['viewcalendar'] = 'Meinen Kalender anzeigen';
$string['viewforumposts'] = 'Meine Forumseinträge anzeigen';
$string['viewmessaging'] = 'Meine Mitteilungen anzeigen';
$string['vieworiginalimage'] = 'Originalbild anzeigen';
$string['visibility'] = 'Sichtbarkeit';
$string['xofyanswered'] = '{$a->completed} von {$a->participants} beantwortet';
$string['xofyattempted'] = '{$a->completed} von {$a->participants} versucht';
$string['xofycontributed'] = '{$a->completed} von {$a->participants} haben mitgewirkt';
$string['xofysubmitted'] = '{$a->completed} von {$a->participants} abgegeben';
$string['xungraded'] = '{$a} nicht bewertet';
$string['youtube'] = 'YouTube';
$string['youtubedesc'] = 'Die URL Ihres YouTube-Kanals.';
$string['showallsectionsdisabled'] = 'Aufgrund der Designsprache ist „Alle Abschnitte auf einer Seite anzeigen“ in Snap nicht verfügbar.';
$string['disabled'] = 'Deaktiviert';
$string['showappearancedisabled'] = 'Die Designsprache von Snap verhindert Änderungen an den Einstellungen von &quot;Darstellung&quot;.';
$string['pbb'] = 'Profilbasierte Marke';
$string['pbb_description'] = 'Durch Aktivieren <strong>des profilbasierten Brandings</strong> können Sie das Branding für eine bestimmte Gruppe von Nutzern/Nutzerinnen basierend auf dem ausgewählten Nutzer/innen-Profilfeld anpassen.
<ul><li>Der Wert des Nutzer/innen-Felds <em>wird mit einem Bindestrich (-) gekennzeichnet</em>. Alle Zeichen werden in Kleinbuchstaben konvertiert und durch einen Bindestrich getrennt.</li>
<li>Die Zeichenfolge <code>snap-pbb-</code> wird vorangestellt.</li>
<li>Diese Klasse wird dem <code>body</code> HTML-Tag hinzugefügt</li></ul>
Beispiel: Der Nutzer/innen-Feldwert <em>Blueberry Extravaganza</em> wird auf <code>snap-pbb-blueberry-extravaganza</code> gesetzt<br /><br />
Diese Funktion wird in Verbindung mit nutzer/innendefiniertem CSS verwendet. Sie müssen CSS-Selektoren mit den neuen Klassen im Abschnitt <a class="snap-settings-tab-link" href="#themesnapbranding">Grundlagen</a> hinzufügen.';
$string['pbb_enable'] = 'Profilbasierte Marke aktivieren';
$string['pbb_enable_description'] = 'Fügt dem Body-Schlagwort die Klasse nur bei Aktivität hinzu.';
$string['pbb_field'] = 'Zu verwendendes Nutzerfeld';
$string['pbb_field_description'] = 'Der Wert dieses Felds wird konvertiert und als CSS-Klassenname mit vorangestelltem <code>snap-pbb-</code> verwendet.';
$string['cachedef_profile_based_branding'] = 'Zwischenspeicherung für profilbasierte Marke.';
$string['cachedef_course_card_bg_image'] = 'Zwischenspeicherung für Kurs-Hintergrundbild.';
$string['cachedef_course_card_teacher_avatar'] = 'Zwischenspeicherung für Trainer-Avatare.';
$string['cachedef_course_card_teacher_avatar_index'] = 'Zwischenspeicherung für den Index der Trainer-Avatare.';
$string['accessforumstringdis'] = 'Anzeigeoptionen';
$string['accessforumstringmov'] = 'Verschiebeoptionen';
$string['accesscalendarstring'] = 'Kalender';
$string['accessglobalsearchstring'] = 'Suche';
$string['admineventwarning'] = 'Wenn Ereignisse aus allen Kursen angezeigt werden sollen,';
$string['gotocalendarsnap'] = 'rufen Sie den Website-Kalender auf.';
$string['quizattemptswarn'] = 'Versuche von gesperrten Nutzer(inne)n ausschließen';
$string['quizfeedback'] = 'Feedback';
$string['validratio'] = 'Diese Farbkombination erfüllt das WCAG 2.0-Mindestverhältnis 4.5:1';
$string['invalidratio'] = 'Diese Farbkombination entspricht nicht dem
<a href="https://www.w3.org/TR/WCAG20-TECHS/G18.html" target="_blank">WCAG 2.0 Mindestverhältniswert 4,5:1</a>. Wert: &quot;{$a}&quot;';
$string['imageinvalidratio'] = 'Bei diesem Schaltflächensymbol können Probleme mit dem Kontrast auftreten, da das WCAG 2.0-Mindestverhältnis 4.5:1 nicht erfüllt ist. Durchschnittlicher Pixelwert: &quot;{$a}&quot;';
$string['catinvalidratio'] = 'Die folgenden Farbkategorien entsprechen nicht dem
<a href="https://www.w3.org/TR/WCAG20-TECHS/G18.html" target="_blank">WCAG 2,0 Mindestwert Verhältnis 4,5:1</a>:
Für die Hintergrundfarbe der Website (weiß): &quot;{$a->white}&quot;. Für die Hintergrundfarbe der Navigationsleiste: &quot;{$a->custombar}&quot;. Hintergrundfarbe der Schaltfläche &quot;Meine Kurse&quot;: &quot;{$a->customnav}&quot;';
$string['imageinvalidratiocategory'] = 'Bei diesem Schaltflächensymbol können Kontrastprobleme mit der Designfarbe auftreten, da das WCAG 2.0-Mindestverhältnis 4.5:1 nicht erfüllt ist. Durchschnittlicher Pixelwert: &quot;{$a}&quot;';
$string['lazyload_mod_page'] = '&quot;Lazy Loading&quot; als Standard für Seitenressourcen aktivieren';
$string['lazyload_mod_page_description'] = 'Wenn diese Einstellung aktiviert ist, werden bei Kursen mit vielen Seiten die Kursseiten erheblich schneller geladen.';
$string['pmadvancedfeed_viewmore'] = 'Mehr anzeigen';
$string['pmadvancedfeed_reload'] = 'Aktualisieren';
$string['multimediacard'] = 'Multimedia-Dateien werden in den Aktivitätskarten-Ansichten für das Snap-Design nicht angezeigt. Diese Einstellung wird nur bei Kurzaktivitäts- und Ressourcenkarten auf der Startseite und den Kursseiten angewendet.';
$string['enabledlogin'] = 'Angezeigte Anmeldeoptionen';
$string['enabledlogindesc'] = 'Wählen Sie die Anmeldeoptionen, die angezeigt werden sollen.';
$string['moodlelogin'] = 'Nur Moodle-Anmeldung anzeigen';
$string['alternativelogin'] = 'Nur alternative Anmeldeoptionen anzeigen';
$string['bothlogin'] = 'Beide Anmeldeoptionen anzeigen';
$string['enabledloginorder'] = 'Reihenfolge der Anmeldeoptionen';
$string['enabledloginorderdesc'] = 'Geben Sie an, welche Anmeldeoption zuerst angezeigt werden soll.';
$string['moodleloginfirst'] = 'Moodle-Anmeldung zuerst anzeigen';
$string['alternativeloginfirst'] = 'Alternative Anmeldeoptionen zuerst anzeigen';
$string['alternativeloginoptions'] = 'Alternative Anmeldeoptionen';
$string['openmessagedrawer'] = 'Öffnen Sie die Messaging-Schublade.';
$string['design_mod_page'] = 'Vorheriges Design für Seitenressourcen aktivieren';
$string['design_mod_page_description'] = 'Wenn diese Option aktiviert ist, wird der Seitenressourceninhalt auf derselben Seite, Kurs- oder Startseite angezeigt.';
$string['refreshdeadlinestask'] = 'Aktualisieren Sie die zwischengespeicherten Daten der Termine. Diese Aktion sollte ausgeführt werden, bevor sich alle Nutzer/innen anmelden.';
$string['resetdeadlinesquerycounttask'] = 'Anzahl der Terminanfragen zurücksetzen';
$string['refreshdeadlinestaskoff'] = 'Es wurde nicht nach Daten zum Auffüllen gesucht. Bitte aktivieren Sie die Option „Abgabetermine mit geplanter Aufgabe aktualisieren“ in den Einstellungen des Menüs „Persönlich“ von Snap, damit diese Aufgabe die zu den Abgabeterminen zwischengespeicherten Daten auffüllen kann.';
$string['activityrestriction'] = 'Aktivitätsbeschränkung';
$string['hideandshowactioncb'] = 'Aktivitäts-Checkbox-Aktion ausblenden und anzeigen';
$string['retryfeed'] = 'Dieser Feed ist derzeit nicht verfügbar. Bitte versuchen Sie es später noch einmal. Feed: {$a}';
$string['loadingfeed'] = 'Ladevorgang läuft... Dies kann einige Zeit in Anspruch nehmen';
$string['hvpcustomcss'] = 'H5P CSS-Anpassungen';
$string['hvpcustomcssdesc'] = 'CSS-Anpassungen für das H5P-Modul (moodle.org/plugins/mod_hvp), falls installiert.';
$string['courselimitstrdanger'] = 'Der Fortschrittsbalken „Kurslimit erreicht“ wird nicht angezeigt.';
$string['courselimitstrwarning'] = 'Wenn mehr als {$a} Kurse aufgeführt sind, wird kein Fortschrittsbalken angezeigt';
$string['tilesformatcoursehomealttext'] = 'Kurs-Startseite';
$string['editmodetiles'] = 'Inhalt bearbeiten';
$string['totop'] = 'Nach oben';
$string['loginsetting'] = 'Anmeldeseite';
$string['loginbgimgheading'] = 'Vorlage für Anmeldeseite';
$string['loginbgimgheadingdesc'] = 'Mit Snap können Sie zwischen verschiedenen Vorlagen wechseln, die auf der Anmeldeseite angezeigt werden. Wählen Sie aus den verschiedenen Optionen im Dropdown-Menü unten aus.';
$string['loginpagetemplate'] = 'Die zu verwendende Anmeldeseite-Vorlage auswählen';
$string['classic_template'] = 'Klassische Vorlage';
$string['loginbgimg'] = 'Hintergrundbild für die Anmeldung';
$string['loginbgimgdesc'] = 'Wählen Sie die Bilder aus, die im Hintergrund der Anmeldeseite angezeigt werden sollen. Um ein besseres Erlebnis mit den angezeigten Bildern zu erzielen, laden Sie Dateien mit einem Seitenverhältnis von 16:9 hoch (Auflösung von 720p oder Abmessungen von 1280 x 720 Pixel). Hochgeladene Bilder müssen die gleichen Abmessungen haben, um eine korrekte Visualisierung zu ermöglichen.';
$string['stylish_template'] = 'Style-Vorlage';
