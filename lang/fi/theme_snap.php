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
 * @copyright  Copyright (c) 2020 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aboutcourse'] = 'Tietoja tästä kurssista';
$string['activity'] = 'Aktiviteetti';
$string['action:changeassetvisibility'] = 'vaihda sivun näkyvyyttä';
$string['action:duplicateasset'] = 'päällekkäinen sivu';
$string['action:changesectionvisibility'] = 'vaihda osion näkyvyyttä';
$string['action:highlightsectionvisibility'] = 'korosta osion näkyvyys';
$string['action:sectiontoc'] = 'hae osion sisällysluettelo';
$string['addanewsection'] = 'Luo uusi osio';
$string['addresourceoractivity'] = 'Luo oppimisaktiviteetti';
$string['admin'] = 'Ylläpito';
$string['advancedbrandingheading'] = 'Lisämukautus';
$string['ago'] = 'sitten';
$string['answered'] = 'Vastattu';
$string['appendices'] = 'Työkalut';
$string['assigndraft'] = 'Luonnos edellyttää vahvistusta';
$string['assignreopened'] = 'Avattu uudelleen';
$string['at'] = 'klo';
$string['attempted'] = 'Yritetty';
$string['basics'] = 'Perusteet';
$string['brandingheading'] = 'Mukautus';
$string['browse'] = 'Selaa';
$string['browseallcourses'] = 'Selaa kaikkia kursseja';
$string['cachedef_activity_deadlines'] = 'Tämän käyttäjien aktiviteettien määräaikojen välimuistisäilö.';
$string['cachedef_generalstaticappcache'] = 'Snapin yleinen kiinteä sovellustason välimuisti';
$string['cachedef_course_completion_progress'] = 'Tämän avulla tallennetaan välimuistiin suoritustiedot per kurssi/käyttäjä.';
$string['cachedef_course_completion_progress_ts'] = 'Tämän avulla voimme mitätöidä istuntotason välimuistit, jos kurssin tai moduulin suoritusasetukset vaihtuvat.';
$string['cachedef_webservicedefinitions'] = 'Tämä on automaattisesti luotavien verkkopalvelumääritelmien välimuistitallennus.';
$string['card'] = 'Kortti';
$string['categoryedit'] = 'Muokkaa kategoriaa';
$string['category_color'] = 'Kategorian väri';
$string['category_color_description'] = 'Kurssikategorian väri. Alakurssit omaksuvat lähimmän yläkategorian määritykset';
$string['category_color_palette'] = 'Väripaletti';
$string['category_color_palette_description'] = 'Katso väriä vastaava heksadesimaaliarvo. Tämä ei vaikuta
määrityksiin, sillä kyseessä on vain esimerkki, jonka avulla käyttäjät voivat luoda määritysarvon.';
$string['changecoverimage'] = 'Vaihda kansikuva';
$string['changefullname'] = 'Muuta sivuston nimeä';
$string['chapters'] = 'Luvut';
$string['choosereadme'] = '<div class="clearfix"><div class="theme_screenshot"><h2>Snap</h2><img class=img-polaroid src="snap/pix/screenshot.jpg" /></div></div>';
$string['conditional'] = 'Ehdollinen';
$string['contents'] = 'Sisältö';
$string['contributed'] = 'Osallistunut';
$string['courses'] = 'Kurssit';
$string['coursecontacts'] = 'Osallistujaluettelo';
$string['coursedisplay'] = 'Kurssinäkymä';
$string['coursefootertoggle'] = 'Kurssin alatunniste';
$string['coursefootertoggledesc'] = 'Kurssin alatunnisteessa näytetään käyttäjille kurssisivulla hyödyllisiä tietoja, muun muassa kurssin yhteyshenkilöt, kurssin kuvaus ja kurssin viimeisimmät aktiviteetit.';
$string['courseformatnotification'] = 'Snap-teema ei tue täysin käyttämääsi nykyistä kurssimuotoa. Jotta voit käyttää Open LMS:ää parhaalla mahdollisella tavalla, suosittelemme, että käytät Snap-teemassa Aiheet- tai Viikoittainen-kurssimuotoa. Voit vaihtaa kurssimuotoa <a href="{$a}">kurssin asetuksissa</a>.';
$string['coursefixydefaulttext'] = 'Et ole tällä hetkellä rekisteröitynyt millekään kurssille.<br>Kurssit, joille olet rekisteröitynyt, näytetään tässä.';
$string['coursegrade'] = 'Kurssin arvosana:';
$string['coursepartialrender'] = 'Ota kurssiosioiden valikoiva lataus käyttöön';
$string['coursepartialrenderdesc'] = 'Jos tämä on käytössä, kurssiosiot ladataan vasta, kun käyttäjä valitsee niitä. Tämä nopeuttaa paljon sisältöä sisältävien kurssien latautumista.';
$string['coursenavigation'] = 'Kurssin siirtymisvalinnat';
$string['coursesummaryfilesunsuitable'] = 'Tyhjennä kurssin kuvauksen tiedostot, ennen kuin yrität vaihtaa kansikuvaa';
$string['coursetools'] = 'Kurssin koontinäyttö';
$string['coverdisplay'] = 'Kansinäyttö';
$string['covercarousel'] = 'Kansikaruselli';
$string['covercarouselon'] = 'Käytä kurssikaruselliä';
$string['covercarouseldescription'] = '<p>Karuselli on joukko vaihtuvia bannereita tai diaesitys, joka näytetään aloitussivulla kansikuvan asemesta.</p>
<p>Voit lisätä enintään kolme kuvaa, otsikon kullekin dialle ja haluamasi tekstityksen. Parhaiten toimivat kuvat, joiden koko on 1200 x 600 pikseliä.</p>';
$string['coverimage'] = 'Kansikuva';
$string['covervideo'] = 'Kansivideo';
$string['comingsoon'] = 'Tulossa pian!';
$string['createsection'] = 'Luo osio';
$string['current'] = 'Nykyinen';
$string['customcss'] = 'Mukautettu CSS';
$string['customcssdesc'] = 'Muista, että tehokkailla muokkaustoiminnoilla voi tehdä myös merkittäviä virheitä. Käyttäjän on korjattava kaikki virheet, jotka johtuvat tässä lisätystä CSS-koodista. Open LMS:n tuki ei auta CSS-sisällön vianmäärityksessä.';
$string['customtopbar'] = 'Siirtymispalkki';
$string['customisenavbar'] = 'Vaihda siirtymispalkin värit';
$string['customisenavbutton'] = 'Vaihda Omat kurssini -painikkeen värit';
$string['customisecustommenu'] = 'Vaihda mukautetun valikon tekstin väriä';
$string['custommenutext'] = 'Mukautetun valikon tekstin väri';
$string['deadlines'] = 'Määräajat';
$string['deadlinestoggle'] = 'Määräajat';
$string['deadlinestoggledesc'] = 'Näytä käyttäjille rekisteröityneiden kurssien tulevien aktiviteettien määräajat.';
$string['defaultsummary'] = 'Lisää aiheen kuvaus tälle alueelle tekstin, kuvien, äänen ja videoiden avulla.';
$string['defaultintrosummary'] = 'Tervetuloa uudelle kurssille {$a}.
<br>Aloita kuvailemalla kurssin sisältöä käyttämällä tekstiä, kuvia, ääntä ja videoita.';
$string['defaulttopictitle'] = 'Nimetön aihe';
$string['debugerrors'] = 'Virheenkorjaus';
$string['deleteassetconfirm'] = 'Poista {$a}';
$string['deletingasset'] = 'Poistetaan kohdetta {$a}';
$string['deletingassetname'] = 'Poistetaan kohdetta {$a->type} nimeltä {$a->name}';
$string['deletesectionconfirm'] = 'Poista osio';
$string['deletingsection'] = 'Poistetaan osiota {$a}';
$string['draft'] = 'Ei julkaistu opiskelijoille';
$string['dropzonelabel'] = 'Pudota liitetiedostot tai <span class="fake-link">selaa</span>';
$string['due'] = 'Määräaika: {$a}';
$string['edit'] = 'Muokkaa kohdetta {$a}';
$string['editcoursecontent'] = 'Muokkaa lohkoja';
$string['editcoursesettings'] = 'Kurssiasetukset';
$string['editcoursetopic'] = 'Muokkaa osiota';
$string['editcustomfooter'] = 'Muokkaa alatunnistetta';
$string['editcustommenu'] = 'Muokkaa mukautettua valikkoa';
$string['error:categorycolorinvalidjson'] = 'Virheellinen JSON-muoto kurssikategorioille';
$string['error:categorycolorinvalidvalue'] = 'Tallenteen tunnus tai väriarvo kategorialle "{$a}" ei kelpaa';
$string['error:categorynotfound'] = 'Kategorian tallennetta tunnuksella "{$a}" ei löytynyt';
$string['error:coverimageexceedsmaxbytes'] = 'Kansikuva ylittää sivuston suurimman sallitun tiedostokoon ({$a})';
$string['error:coverimageresolutionlow'] = 'Parhaan laadun takaamiseksi suosittelemme kuvaa, jonka leveys on vähintään 1024 pikseliä.';
$string['error:duplicatedcategoryids'] = 'Virheellinen JSON-muoto, osa tunnuksista esiintyy kahdesti';
$string['error:failedtochangeassetvisibility'] = 'Sivun näyttäminen/piilottaminen epäonnistui';
$string['error:failedtochangesectionvisibility'] = 'Osion näyttäminen/piilottaminen epäonnistui';
$string['error:failedtohighlightsection'] = 'Osion korostaminen epäonnistui';
$string['error:failedtoduplicateasset'] = 'Kopiointi epäonnistui';
$string['error:failedtodeleteasset'] = 'Sivun poistaminen epäonnistui';
$string['error:failedtotoc'] = 'Sisällysluettelon hakeminen epäonnistui.';
$string['extension'] = 'Tiedostomuoto: {$a}';
$string['facebook'] = 'Facebook';
$string['facebookdesc'] = 'Tämä on Facebook-sivusi URL-osoite.';
$string['favicon'] = 'Favicon';
$string['favicondesc'] = 'Favicon-kuvakkeet selaimen osoiterivillä, käyttäjän kirjanmerkeissä ja mobiilipikakuvakkeissa.';
$string['favorite'] = 'Suosikki: {$a}';
$string['favorited'] = 'Lisätty suosikkeihin: {$a}';
$string['featurespots'] = 'Toimintomainokset';
$string['featurespotsedit'] = 'Muokkaa toimintomainoksia';
$string['featurespotshelp'] = '<p>Voit lisätä sivustosi etusivulle jopa kolme toimintomainosta, joilla voit esitellä sivuston hyödyllisiä toimintoja nykyisille ja mahdollisille käyttäjille.</p>
<p>Lisää otsikko, sisältö ja kuva (vapaaehtoinen) jokaiselle toiminnolle. Kuvien tulisi olla neliönmuotoisia ja kooltaan enintään 200 x 200 pikseliä.</p>';
$string['featurespotsheading'] = 'Toimintomainosten otsikko';
$string['featureonetitle'] = 'Toiminnon 1 otsikko';
$string['featuretwotitle'] = 'Toiminnon 2 otsikko';
$string['featurethreetitle'] = 'Toiminnon 3 otsikko';
$string['featureonetitlelink'] = 'Toiminnon 1 otsikon linkki';
$string['featuretwotitlelink'] = 'Toiminnon 2 otsikon linkki';
$string['featurethreetitlelink'] = 'Toiminnon 3 otsikon linkki';
$string['featuretitlelinkdesc'] = 'Kirjoita verkko-osoite, johon haluat linkittää tämän toiminnon esittelyn. Voit lisätä sivustoosi ulkoisia tai sisäisiä linkkejä. Voit lisätä sisäisen linkin kopioimalla sen verkko-osoitteesta merkin / jälkeen. Esimerkiksi kurssin linkissä haluttu osoite olisi course/view.php?id=160. Voit lisätä ulkoisen linkin aloittamalla linkin https://.';
$string['featureonetitlecb'] = 'Toiminto 1 avautuu uudessa ikkunassa';
$string['featuretwotitlecb'] = 'Toiminto 2 avautuu uudessa ikkunassa';
$string['featurethreetitlecb'] = 'Toiminto 3 avautuu uudessa ikkunassa';
$string['featuretitlecbdesc'] = 'Jos tämä on käytössä, esittelyyn lisätty linkki avataan uudessa ikkunassa';
$string['featureonetext'] = 'Toiminnon 1 sisältö';
$string['featuretwotext'] = 'Toiminnon 2 sisältö';
$string['featurethreetext'] = 'Toiminnon 3 sisältö';
$string['featureoneimage'] = 'Toiminnon 1 kuva';
$string['featuretwoimage'] = 'Toiminnon 2 kuva';
$string['featurethreeimage'] = 'Toiminnon 3 kuva';
$string['featuredcourses'] = 'Esitellyt kurssit';
$string['featuredcourseshelp'] = 'Voit esitellä kurssisi etusivulla jopa kahdeksan kurssi. Jos haluat esitellä kurssia, kirjoita sen kurssitunnus.';
$string['featuredcoursesheading'] = 'Esiteltyjen kurssien otsikko';
$string['featuredcourseone'] = 'Esitelty kurssi 1';
$string['featuredcoursetwo'] = 'Esitelty kurssi 2';
$string['featuredcoursethree'] = 'Esitelty kurssi 3';
$string['featuredcoursefour'] = 'Esitelty kurssi 4';
$string['featuredcoursefive'] = 'Esitelty kurssi 5';
$string['featuredcoursesix'] = 'Esitelty kurssi 6';
$string['featuredcourseseven'] = 'Esitelty kurssi 7';
$string['featuredcourseeight'] = 'Esitelty kurssi 8';
$string['featuredcoursesedit'] = 'Muokkaa esiteltyjä kursseja';
$string['featuredcoursesbrowseall'] = 'Selaa kaikkia kursseja';
$string['featuredcoursesbrowsealldesc'] = 'Lisää Selaa kaikkia kursseja -linkki';
$string['feedbackavailable'] = 'Palaute saatavilla';
$string['feedbacktoggle'] = 'Palaute ja arviointi';
$string['feedbacktoggledesc'] = 'Näytä opiskelijoille heidän viimeisin palautteensa ja opettajille viimeisimmät palautukset, jotka täytyy arvioida.';
$string['footnote'] = 'Sivuston alatunniste';
$string['footnotedesc'] = 'Alatunniste näytetään kaikkialla sivustossa. Se on paras paikka lisätä linkkejä ohjeisiin, tukeen ja muihin organisaation sivustoihin, jotka haluat jakaa opiskelijoiden ja opettajien kanssa (voit lisätä siihen esimerkiksi kirjaston tai sähköpostiosoitteen).';
$string['forcepwdwarningpersonalmenu'] = 'Sinun täytyy <a href="{$a}">vaihtaa salasanasi</a> ennen henkilökohtaisen valikon käyttöä.';
$string['forumauthor'] = 'Tekijä';
$string['forumlastpost'] = 'Viimeisin viesti';
$string['forumpicturegroup'] = 'Ryhmä';
$string['forumreplies'] = 'Vastaukset';
$string['forumtopic'] = 'Aihe';
$string['forumposts'] = 'Keskustelualueen viestit';
$string['forumpoststoggle'] = 'Keskustelualueen viestit';
$string['forumpoststoggledesc'] = 'Näytä käyttäjille heidän kurssiensa 10 viimeisintä keskustelualueen viestiä';
$string['fullname'] = 'Sivuston nimi';
$string['fullnamedesc'] = 'Sivuston nimi.';
$string['graderadviseuserreport'] = 'Arvioijan raportti ei toimi hyvin mobiililaitteissa. Sen sijaan kannattaa käyttää käyttäjän raporttia.';
$string['grading'] = 'Arviointi';
$string['help'] = 'Ohje';
$string['helpguide'] = 'Ohjeopas';
$string['headingfont'] = 'Otsikon fontti';
$string['headingfont_desc'] = 'Tätä fonttia käytetään koko sivuston otsikoissa (elementit h1 - h6). Jos haluat sisällyttää mukautetun verkkofontin, muista lisätä se Moodlen extra html -lomakkeeseen.';
$string['helpwithlogin'] = 'Apua kirjautumiseen';
$string['helpwithloginandguest'] = 'Apua kirjautumiseen / vierailijakäyttöön';
$string['hiddencoursestoggle'] = 'Piilotetut kurssit';
$string['highlightedsection'] = 'korostettu';
$string['home'] = 'etusivu';
$string['image'] = 'kuva';
$string['images'] = 'Kuvat';
$string['instagram'] = 'Instagram';
$string['instagramdesc'] = 'Tämä on Instagram-tilisi URL-osoite.';
$string['introduction'] = 'Johdanto';
$string['jsontext'] = 'JSON-teksti';
$string['jsontextdescription'] = 'Tekstialue vahvistaa annetun JSON:n, joten vain olemassa olevat kategoriat sallitaan,
vain numeeriset arvot tunnustietueina (kategoriatietueet) kelpaavat ja väreinä hyväksytään vain heksadesimaaliarvot.
Esimerkki:<br>
{"1":"#FAAFFF",<br>
"45":"#AFF",<br>
"65":"#FFF228",<br>
"12":"#CC0084",<br>
"56":"#CC0087",<br>
"89":"#CCF084"}';
$string['knowledgebase'] = 'Open LMS -tietämyskanta';
$string['list'] = 'Luettelo';
$string['linkedin'] = 'LinkedIn';
$string['linkedindesc'] = 'Tämä on organisaation LinkedIn-tilin URL-osoite.';
$string['leftnav'] = 'Sisällysluettelo';
$string['leftnavdesc'] = 'Valitse, missä sisällysluettelo näytetään. Luettelo antaa enemmän tilaa sisällölle, joten se sopii hyvin kursseille, joilla on monta aihetta.';
$string['loading'] = 'Ladataan...';
$string['loggedinasguest'] = 'Olet kirjautunut vierailijana';
$string['loggedoutmsg'] = 'Olet kirjautunut ulos. Jos haluat jatkaa sivuston käyttöä, kirjaudu takaisin sisään.';
$string['loggedoutmsgtitle'] = 'Olet kirjautunut ulos';
$string['loggedoutfailmsg'] = 'Sinun täytyy kirjautua sisään, jos haluat toimia seuraavasti: {$a}.';
$string['loginform'] = 'Kirjaudu';
$string['logo'] = 'Logo';
$string['logodesc'] = 'Logo näytetään ylätunnisteessa kaikkialla sivustossa.';
$string['menu'] = 'Omat kurssini';
$string['messageread'] = 'Viesti luettu';
$string['messages'] = 'Viestit';
$string['messagestoggle'] = 'Viestit';
$string['messagestoggledesc'] = 'Näytä käyttäjille heidän viimeisimmät saamansa viestit edellisen 12 viikon ajalta.';
$string['more'] = 'Lisää';
$string['morenews'] = 'Lisää uutisia';
$string['movingstartedhelp'] = 'Siirry kohtaan, johon haluat sijoittaa osion {$a}';
$string['movingdropsectionhelp'] = 'Sijoita osio {$a->moving} ennen osiota {$a->before}';
$string['moving'] = 'Siirretään {$a}';
$string['movingcount'] = 'Siirretään {$a} objektia';
$string['movefailed'] = 'Kohteen {$a} siirto epäonnistui';
$string['move'] = 'Siirrä {$a}';
$string['movehere'] = 'Siirrä tähän';
$string['movesection'] = 'Siirrä osio';
$string['navbarbg'] = 'Taustaväri';
$string['navbarlink'] = 'Tekstiväri';
$string['navbarbuttoncolor'] = 'Taustaväri';
$string['navbarbuttonlink'] = 'Tekstiväri';
$string['nextsection'] = 'Seuraava osio';
$string['nodeadlines'] = 'Sinulla ei ole tulevia määräaikoja.';
$string['noforumposts'] = 'Sinulla ei ole aiheeseen liittyviä keskustelualueen viestejä.';
$string['nograded'] = 'Sinulla ei ole viimeaikaista palautetta.';
$string['nograding'] = 'Sinulla ei ole arvioitavia palautuksia.';
$string['nomessages'] = 'Sinulla ei ole viestejä.';
$string['notanswered'] = 'Ei vastattu';
$string['notattempted'] = 'Ei yritetty';
$string['notcontributed'] = 'Ei osallistuttu';
$string['notpublished'] = 'Ei julkaistu opiskelijoille';
$string['notsubmitted'] = 'Ei palautettu';
$string['overdue'] = 'Myöhässä';
$string['personalmenu'] = 'Henkilökohtainen valikko';
$string['personalmenufeatures'] = 'Henkilökohtaisen valikon toiminnot';
$string['personalmenulogintoggle'] = 'Näytä henkilökohtainen valikko kirjauduttaessa';
$string['personalmenulogintoggledesc'] = 'Avaa henkilökohtaisen valikon heti kirjautumisen jälkeen';
$string['personalmenuadvancedfeedsenable'] = 'Ota kehittyneet syötteet käyttöön (testausvaiheessa)';
$string['personalmenuadvancedfeedsenabledesc'] = 'Kehittyneet syötteet lataavat joitain henkilökohtaisia valikkokohteita, mikä mahdollistaa nopeammat latausajat ja päivittää sisältöä tarpeen mukaan.';
$string['personalmenuadvancedfeedsperpage'] = 'Kehittyneiden syötteiden näytettävien kohteiden määrä';
$string['personalmenuadvancedfeedsperpagedesc'] = 'Valitse, montako kohdetta syötteessä näytetään. Käyttäjät näyttää lisää kohteita valitsemalla <strong>Näytä lisää</strong>.';
$string['pld'] = 'PLD';
$string['pluginname'] = 'Snap';
$string['poster'] = 'Kansikuva';
$string['posterdesc'] = 'Tämä on sivuston etusivulla näytettävä suuri ylätunnistekuva. Parhaiten toimivat vaakasuuntaiset kuvat, joiden koko on vähintään 1 200 x 600 pikseliä.';
$string['poweredbyrunby'] = 'Luotu <a href="https://www.openlms.net/" target="_blank" rel="noopener">Open LMS:llä</a>,
     joka on <a href="https://moodle.com/" target="_blank" rel="noopener">Moodleen</a> perustuva tuote.<br>
    Copyright &#169; {$a} Open LMS. Kaikki oikeudet pidätetään.';
$string['previoussection'] = 'Edellinen osio';
$string['privacy:metadata:theme_snap_course_favorites:courseid'] = 'Käyttäjän suosikkeihin lisäämän kurssin tunnus';
$string['privacy:metadata:theme_snap_course_favorites:userid'] = 'Suosikkeihin kurssin lisänneen käyttäjän käyttäjätunnus';
$string['privacy:metadata:theme_snap_course_favorites:timefavorited'] = 'Aikaleima, joka näyttää, milloin käyttäjä lisäsi kurssin suosikkeihin';
$string['privacy:metadata:theme_snap_course_favorites'] = 'Tallentaa käyttäjän kurssisuosikit Snapissa';
$string['problemsfound'] = 'Löydetyt ongelmat';
$string['progress'] = 'Edistyminen';
$string['readmore'] = 'Lue lisää&nbsp;»';
$string['recentactivity'] = 'Viimeisin toiminta';
$string['recentfeedback'] = 'Palaute';
$string['region-main'] = 'Pääalue';
$string['region-side-main-box'] = 'Pääalue';
$string['region-side-post'] = 'Oikea';
$string['region-side-pre'] = 'Vasen';
$string['region-side-top'] = 'Yläosa';
$string['released'] = 'Julkaistu: {$a}';
$string['reopened'] = 'Avattu uudelleen';
$string['resourcedisplay'] = 'Aineistonäyttö';
$string['resourcedisplayhelp'] = 'Valitse, miten liitteet ja linkit näytetään kurssilla. Snap-teema ei tue multimediatiedostoja pienissä aktiviteeteissa ja aineistokorttien kuvauksissa.';
$string['displaydescription'] = 'Näytä kuvaus';
$string['displaydescriptionhelp'] = 'Jos haluat näyttää aineiston kuvauksen ja URL-aktiviteetit ensin uudella sivulla, valitse tämä. Opiskelijat käyttävät sisältöä kuvauksesta.';
$string['search'] = 'Etsi sisältöä';
$string['seriffont'] = 'Serif-fontti';
$string['seriffont_desc'] = 'Tätä fonttia käytetään suurimmassa osassa käyttäjän luomaa sisältöä. Serif-fontin käyttäminen käyttäjän luomassa sisällössä parantaa luettavuutta ja saa tekstin näyttämään ihmisen kirjoittamalta.';
$string['showcoursegradepersonalmenu'] = 'Arvosanat';
$string['showcoursegradepersonalmenudesc'] = 'Näyttää käyttäjille heidän arvosanansa henkilökohtaisen valikon kurssikorteissa';
$string['socialmedia'] = 'Sosiaalinen media';
$string['submitted'] = 'Palautettu';
$string['sitedescription'] = 'Sivuston kuvaus';
$string['subtitle'] = 'Alaotsikko';
$string['subtitle_desc'] = 'Kuvaile lyhyesti sivustoasi käyttäjille.';
$string['themecolor'] = 'Sivuston väri';
$string['themecolordesc'] = 'Kirkkaat värit toimivat parhaiten – ne myös antavat sivustollesi nykyaikaisen vaikutelman.';
$string['title'] = 'Otsikko';
$string['top'] = 'Ylä';
$string['topbarbgcolor'] = 'Siirtymispalkin väri';
$string['topbarlinkcolor'] = 'Siirtymispalkin linkki- ja kuvakeväri';
$string['topbarbuttoncolor'] = 'Omien kurssien tausta';
$string['togglenavigation'] = 'Vaihda siirtymistilaa';
$string['topicactions'] = 'Aiheen toiminnot';
$string['twitter'] = 'Twitter';
$string['twitterdesc'] = 'Tämä on Twitter-tilisi URL-osoite.';
$string['unenrolme'] = 'Poista rekisteröityminen';
$string['unread'] = 'lukematta';
$string['unsupportedcoverimagetype'] = 'Kansikuvatyyppiä {$a} ei tueta';
$string['via'] = '-';
$string['viewcourse'] = 'Näytä kurssi';
$string['viewyourprofile'] = 'Näytä profiili';
$string['viewmyfeedback'] = 'Näytä oma palaute';
$string['viewcalendar'] = 'Näytä oma kalenteri';
$string['viewforumposts'] = 'Näytä omat keskustelualueen viestit';
$string['viewmessaging'] = 'Näytä omat viestit';
$string['vieworiginalimage'] = 'Näytä alkuperäinen kuva';
$string['visibility'] = 'Näkyvyys';
$string['xofyanswered'] = '{$a->completed}/{$a->participants} vastannut';
$string['xofyattempted'] = '{$a->completed}/{$a->participants} yrittänyt';
$string['xofycontributed'] = '{$a->completed}/{$a->participants} osallistunut';
$string['xofysubmitted'] = '{$a->completed}/{$a->participants} palauttanut';
$string['xungraded'] = '{$a} arvioimatta';
$string['youtube'] = 'YouTube';
$string['youtubedesc'] = 'Tämä on YouTube-kanavasi URL-osoite.';
$string['showallsectionsdisabled'] = 'Design-kielen takia "Näytä kaikki osiot yhdellä sivulla" ei ole käytettävissä Snapissa.';
$string['disabled'] = 'Ei käytössä';
$string['showappearancedisabled'] = 'Snapin suunnittelukieli estää muutokset ulkoasuasetuksiin.';
$string['pbb'] = 'Profiiliperusteinen brändäys';
$string['pbb_description'] = 'Kun otat käyttöön <strong>profiiliperusteisen brändäyksen</strong>, voit muokata tiettyjen käyttäjäryhmien brändäystä valitun käyttäjäprofiilikentän perusteella.
<ul><li>Käyttäjäkentän arvo <em>muunnetaan</em>. Tämä muuntaa kaikki merkit pieniksi ja erottaa ne yhdysviivalla (-)</li>
<li>Merkkijono <code>snap-pbb-</code> liitetään eteen.</li>
<li>Tämä luokka lisätään HTML-tunnisteeseen <code>body</code>.</li></ul>
Jos käyttäjäkentän arvo on esimerkiksi <em>Blueberry Extravaganza</em>, muuntamisen jälkeen se on <code>snap-pbb-blueberry-extravaganza</code>.<br /><br />
Tätä toimintoa käytetään yhdessä <a href="https://help.blackboard.com/Blackboard_Open_LMS/Administrator/Manage_a_Site/Course_and_Site_Design/Themes/Snap#advanced-branding_OTP-3" target="_blank">mukautetun CSS:n</a> kanssa,
joten sinun täytyy lisätä CSS-valitsimet <a class="snap-settings-tab-link" href="#themesnapbranding">perusteiden</a> osion uusilla luokilla.';
$string['pbb_enable'] = 'Ota profiiliperusteinen brändäys käyttöön';
$string['pbb_enable_description'] = 'Lisää luokan body-tunnisteeseen vain, jos aktiivinen.';
$string['pbb_field'] = 'Käytettävä käyttäjäkenttä';
$string['pbb_field_description'] = 'Tämän kentän arvo muunnetaan ja sitä käytetään CSS-luokan nimenä siten, että sen eteen lisätään <code>snap-pbb-</code>.';
$string['cachedef_profile_based_branding'] = 'Tämä profiiliperusteinen brändäyksen välimuistipalvelu-';
$string['cachedef_course_card_bg_image'] = 'Tämä on kurssin taustakuvan välimuistipalvelu.';
$string['cachedef_course_card_teacher_avatar'] = 'Tämä on opettajien avatareiden välimuistipalvelu.';
$string['cachedef_course_card_teacher_avatar_index'] = 'Tämä on opettajien avatareiden indeksin välimuistipalvelu.';
$string['accessforumstringdis'] = 'Näyttöasetukset';
$string['accessforumstringmov'] = 'Siirtämisasetukset';
$string['accesscalendarstring'] = 'Kalenteri';
$string['accessglobalsearchstring'] = 'Hae';
$string['admineventwarning'] = 'Jos haluat nähdä kaikkien kurssien tapahtumat, ';
$string['gotocalendarsnap'] = 'siirry sivuston kalenteriin.';
$string['quizattemptswarn'] = 'Jättää pois jäädytettyjen käyttäjien suorituskerrat';
$string['quizfeedback'] = 'Palaute';
$string['validratio'] = 'Tämä väriyhdistelmä ei noudata WCAG 2.0:n minimisuhdearvoa 4.5:1';
$string['invalidratio'] = 'Tämä väriyhdistelmä ei noudata
<a href="https://www.w3.org/TR/WCAG20-TECHS/G18.html" target="_blank">WCAG 2.0:n minimisuhdearvoa 4.5:1</a>. Arvo: {$a}';
$string['imageinvalidratio'] = 'Tässä kuvassa saattaa olla kontrastiongelmia, jotka johtuvat poikkeamisesta WCAG 2.0:n vähimmäismittasuhdearvosta 4.5:1.Keskimääräinen pikseliarvo: {$a}';
$string['catinvalidratio'] = 'Seuraavat värikategoriat eivät noudata
<a href="https://www.w3.org/TR/WCAG20-TECHS/G18.html" target="_blank">WCAG 2.0:n minimisuhdearvoa 4.5:1</a>:
Sivun taustaväriä (valkoinen) vasten: {$a->white}. Siirtymispalkin taustaväriä vasten: {$a->custombar}. Omat kurssit -painikkeen taustaväriä vasten: {$a->customnav}';
$string['imageinvalidratiocategory'] = 'Tässä kuvassa voi olla kontrastiongelmia teeman väriä vasten johtuen siitä, ettei se noudata WCAG 2.0:n minimimittasuhdearvoa 4.5:1. Keskimääräinen pikseliarvo: {$a}';
$string['lazyload_mod_page'] = 'Ota sivuaineistojen valikoiva lataus käyttöön';
$string['lazyload_mod_page_description'] = 'Jos tämä asetus on käytössä, se nopeuttaa merkittävästi sivujen latausaikoja kursseilla, joilla on monia sivuja.';
$string['pmadvancedfeed_viewmore'] = 'Näytä lisää';
$string['pmadvancedfeed_reload'] = 'Päivitä';
$string['multimediacard'] = 'Multimediatiedostoja ei näytetä Snap-teeman aktiviteettikorttinäkymissä. Tätä käytetään vain aloitussivulla ja kurssisivuilla vian pienissä aktiviteeteissa ja aineistokorteissa.';
$string['enabledlogin'] = 'Näytetyt kirjautumisvaihtoehdot';
$string['enabledlogindesc'] = 'Valitse näytettävät kirjautumisvaihtoehdot.';
$string['moodlelogin'] = 'Näytä vain Moodle-kirjautuminen';
$string['alternativelogin'] = 'Näytä vain vaihtoehtoiset kirjautumistavat';
$string['bothlogin'] = 'Näytä molemmat kirjautumisvaihtoehdot';
$string['enabledloginorder'] = 'Kirjautumisvaihtoehtojen järjestys';
$string['enabledloginorderdesc'] = 'Valitse, kumpi kirjautumisvaihtoehto näytetään ensimmäisenä.';
$string['moodleloginfirst'] = 'Näytä Moodle-kirjautuminen ensimmäisenä';
$string['alternativeloginfirst'] = 'Näytä vaihtoehtoiset kirjautumistavat ensin';
$string['alternativeloginoptions'] = 'Vaihtoehtoiset kirjautumistavat';
