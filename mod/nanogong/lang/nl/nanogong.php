<?php

// Dit bestand is onderdeel van Moodle - http://moodle.org/
//
// Moodle is gratis software: u mag dit verder distribueren en/of aanpassen
// onder de voorwaarden van de GNU General Public License zoals gepubliceerd door
// de Free Software Foundation, ofwel versie 3 van de Licentie, of
// iedere latere versie naar keuze.
//
// Moodle wordt gedistribueerd in de hoop dat het nuttig is,
// maar ZONDER ENIGE GARANTIE; zelfs zonder de geïmpliceerde garantie van
// VERKOOPBAARHEID of GESCHIKTHEID VOOR EEN BEPAALD DOEL. Zie de
// GNU General Public License voor meer informatie.
//
// U zou een exemplaar van de GNU General Public License ontvangen moeten hebben
// bij Moodle.  Zie indien dit niet het geval is <http://www.gnu.org/licenses/>.

/**
 * Nederlandse strings voor nanogong
 *
 * @author     Joost Elshoff
 * @package    mod
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 of later
 * @version    4.2.3
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'NanoGong voice activiteit';
$string['modulenameplural'] = 'NanoGong activiteiten';
$string['modulename_help'] = 'De NanoGong voice module laat deelnemers hun stem opnemen, afspelen en insturen. Als de opname afgespeeld wordt, kan de deelnemer deze versnellen of vertragen zonder hem te wijzigen.';
$string['nanogongfieldset'] = 'Toon inhoud met NanoGong voice';
$string['nanogongname'] = 'Naam van deze NanoGong voice activiteit';
$string['nanogong'] = 'NanoGong voice';
$string['pluginadministration'] = 'NanoGong voice beheer';
$string['pluginname'] = 'NanoGong Audio';
$string['invalidid'] = 'Ongeldige nanogong ID';

$string['availabledate'] = 'Beschikbaar vanaf';
$string['description'] = 'Beschrijving';
$string['duedate'] = 'Inleveren voor';
$string['maxgrade'] = 'Maximaal cijfer';
$string['maxduration'] = 'Duur (secs)';
$string['maxduration_help'] = 'Maximale duur van iedere opname in seconden (1 - 1200)';
$string['maxdurationdetail'] = 'Maximale duur van iedere opname (secs)';
$string['maxnumber'] = 'Toegestane opnames';
$string['maxnumber_help'] = 'Maximum aantal toegestane opnames voor iedere student (0 betekent geen beperkingen)';
$string['maxnumberdetail'] = 'Maximum aantal opnames';
$string['preventlate'] = 'Voorkom late inzendingen';
$string['permission'] = 'Hebben de studenten toegang tot de opnames van anderen?';

$string['submission'] = 'Ingeleverd';
$string['grade'] = 'Cijfer';
$string['comment'] = 'Bericht van de docent voor de student';
$string['edit'] = 'Klik hier om te bewerken';
$string['show'] = 'Tonen';
$string['submit'] = 'Indienen';
$string['insert'] = 'Verwijderd, klik hier om opnieuw in te voegen';
$string['add'] = 'Een nieuwe opname toevoegen';
$string['delete'] = 'Geselecteerde opname verwijderen';
$string['leavemessage'] = 'Voeg bericht voor docent toe of bewerken';
$string['cancel'] = 'Annuleren';
$string['yes'] = 'Ja';
$string['no'] = 'Nee';
$string['view'] = 'Bekijk';
$string['hide'] = 'Verberg';
$string['backtolist'] = 'Terug naar de lijst met studenten';
$string['supplement'] = 'Bericht';
$string['reverse'] = 'in omgekeerde chronologische volgorde';
$string['chronological'] = 'in chronologische volgorde';
$string['reversebutton'] = 'Veranderen naar omgekeerd chronologische volgorde';
$string['chronologicalbutton'] = 'Veranderen naar chronologische volgorde';
$string['recording'] = 'Opname';

$string['nanogong:view'] = 'NanoGong voice bekijken';
$string['nanogong:submit'] = 'NanoGong voice indienen';
$string['nanogong:grade'] = 'NanoGong voice beoordelen';

$string['nanogongaudios'] = 'NanoGong voice opnames';
$string['nanogongmessages'] = 'NanoGong voice berichten';

$string['tablename'] = 'Naam';
$string['tableyourmessage'] = 'Opnamelijst';
$string['tablemessage'] = 'Uw opnames';
$string['tablevoice'] = 'Gesproken feedback van docent';
$string['tablemodified'] = 'Voor het laatst gewijzigd op';

$string['modulenamefull'] = 'NanoGong voice opname';
$string['nolimitation'] = 'Geen beperking';
$string['voicetitle'] = 'Wat is het onderwerp van uw opname?';
$string['titlemax'] = '(max. 30 letters)';
$string['voicerecording'] = 'Uw voice opname';
$string['submitted'] = 'Ingediend';
$string['gradedon'] = 'Gewijzigd op';
$string['instructions'] = 'Klik op een opname hieronder';
$string['checkdeletemessage'] = 'Weet je zeker dat je deze opname wilt verwijderen?';
$string['checkdeletemessages'] = 'Weet je zeker dat je de geselecteerde opname(s) wilt verwijderen??';
$string['inonepage'] = 'student(en) op een pagina';
$string['recordinginonepage'] = 'opname(s) op een pagina';
$string['all'] = 'Alle';
$string['submiitedrecordings'] = 'Studenten die opdrachten hebben ingeleverd';
$string['gradedstudents'] = 'Who have submitted recordings that have been graded';
$string['ungradedstudents'] = 'Who have submitted recordings that have not been graded';
$string['studentsnosubmissions'] = 'Studenten zonder ingeleverde opdrachten';
$string['allstudents'] = 'Alle studenten';
$string['recordingtempname'] = 'Opname ';
$string['otherrecording'] = 'Opnames van alle studenten';
$string['previouspage'] = 'Vorige pagina';
$string['nextpage'] = 'Volgende pagina';
$string['listof'] = 'Opnamelijst voor ';
$string['feedbackfor'] = 'Your feedback for ';
$string['submissionintotal'] = ' student in totaal';
$string['submissionsintotal'] = ' studenten in totaal';
$string['thereis'] = 'Er is ';
$string['thereare'] = 'Er zijn ';
$string['submissionsfrom'] = 'ingeleverde opdrachten van ';
$string['thereareno'] = 'Er zijn geen ';
$string['voicerecorded'] = 'Uw opnamegeschiedenis';
$string['timecreated'] = 'Gemaakt op';
$string['title'] = 'Titel';
$string['inuse'] = 'In gebruik';
$string['messagefor'] = 'Bericht voor je docent (kan niet gezien worden door anderen)';
$string['messagefrom'] = 'Bericht van student voor docent';
$string['page'] = 'Pagina ';
$string['deletealertmessage'] = 'Selecteer eerst een opname';
$string['student'] = 'Student ';
$string['emptymessage'] = 'Er is nog niets opgenomen.';
$string['with'] = 'met';
$string['allcategory'] = 'studenten in totaal';
$string['submittedcategory'] = 'studenten die een opname hebben ingeleverd';
$string['gradedcategory'] = 'studenten met opnames die beoordeeld zijn';
$string['ungradedcategory'] = 'studenten met opnames die nog niet beoordeeld zijn';
$string['unsubmittedcategory'] = 'studenten zonder ingeleverde opnames';
$string['allcategoryone'] = 'studenten in totaal';
$string['submittedcategoryone'] = 'student die opnames heeft ingeleverd';
$string['gradedcategoryone'] = 'student met opnames die beoordeeld zijn';
$string['ungradedcategoryone'] = 'student met opnames die nog niet beoordeeld zijn';
$string['unsubmittedcategoryone'] = 'student zonder ingeleverde opnames';
$string['notavailable'] = 'Dit is momenteel niet beschikbaar';
$string['changetorecordings'] = 'Toon opnames van alle studenten in chronologische volgorde';
$string['changetostudents'] = 'Toon opnames van iedere student om feedback te geven';
$string['requiredfield'] = 'Er zijn verplichte velden in dit formulier aangegeven';
$string['nofeeadback'] = 'Momenteel is er geen feedback van je docent';
$string['notes'] = 'Notities';
$string['submittedtime'] = 'Tijd van inleveren';
$string['feedbacktitle'] = 'Feedback from the teacher';
$string['nosubmission'] = 'Niets ingeleverd';
$string['loadcurrent'] = 'Huidige voice feedback laden in deze applet';
$string['yourmessage'] = 'Uw bericht voor de student';
$string['outof'] = ' van de ';
$string['studentlist'] = 'Opnames van iedere student';
$string['wronggrade'] = 'Geef een getal tussen de 0 en ';
$string['voicefeedback'] = 'Gesproken feedback';
$string['commentmessage'] = 'Bericht van je docent';
$string['forentering'] = 'om feedback te geven';
$string['messagearea'] = 'Je bericht voor de docent';
$string['imgtitle'] = 'Toon/verberg de NanoGong speler door op dit icoon te klikken';
$string['servererror'] = 'Er is een probleem met het versturen van de opname naar de server. Probeer het later nogmaals!';
$string['showdeleteboxes'] = 'KLik hier om de opname te selecteren die je wilt verwijderen';
$string['isdelete'] = 'Verwijderen?';
$string['deleterecordings'] = 'De geselecteerde opnames verwijderen';
$string['lockstudent'] = 'Verdere aanpassingen voor deze student vergrendelen';
$string['submissionlocked'] = 'Deze inzending is vergrendeld voor verdere aanpassingen';

?>
