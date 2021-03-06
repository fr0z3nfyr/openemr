<?php
/**
 * Viewing and modification/creation of office notes.
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @link    http://www.open-emr.org
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

include_once("../../globals.php");

$oNoteService = new \services\ONoteService();

//the number of records to display per screen
$N = 10;

$offset = (isset($_REQUEST['offset'])) ? $_REQUEST['offset'] : 0;
$active = (isset($_REQUEST['active'])) ? $_REQUEST['active'] : -1;

//this code handles changing the state of activity tags when the user updates them through the interface
if (isset($_POST['mode'])) {
    if ($_POST['mode'] == "update") {
        foreach ($_POST as $var => $val) {
            if ($val == "true" || $val == "false") {
                $id = str_replace("act","",$var);
                if ($val == "true") {
                    $result = $oNoteService->enableNoteById($id);
                } elseif($val=="false") {
                    $oNoteService->disableNoteById($id);
                }
            }
        }
    } elseif ($_POST['mode'] == "new") {
        $oNoteService->add($_POST["note"]);
    }
}
?>
<html>
<head>

<?php require($GLOBALS['srcdir'] . '/templates/standard_header_template.php'); ?>

</head>
<body class="body_top">

<div id="officenotes_edit">

<form method="post" name="new_note" action="office_comments_full.php" onsubmit='return top.restoreSession()'>

<?php
/* BACK should go to the main Office Notes screen */
if ($userauthorized) { $backurl="office_comments.php"; }
else { $backurl="../main_info.php"; }
?>

<a href="office_comments.php" onclick='top.restoreSession()'>

<span class="title"><?php echo xlt('Office Notes'); ?></span>
<span class="back"><?php echo text($tback); ?></span></a>

<br>
<input type="hidden" name="mode" value="new">
<input type="hidden" name="offset" value="<?php echo attr($offset); ?>">
<input type="hidden" name="active" value="<?php echo attr($active); ?>">

<textarea name="note" class="form-control" rows="3" placeholder="<?php echo xla("Enter new office note here"); ?>" ></textarea>
<input type="submit" value="<?php echo xla('Add New Note'); ?>" />
</form>

<br/>
<hr>

<form method="post" name="update_activity" action="office_comments_full.php" onsubmit='return top.restoreSession()'>

<?php //change the view on the current mode, whether all, active, or inactive
if ($active==="1") { $inactive_class="_small"; $all_class="_small"; }
elseif ($active==="0") { $active_class="_small"; $all_class="_small";}
else { $active_class="_small"; $inactive_class="_small";}
?>

<a href="office_comments_full.php?offset=0&active=-1" class="css_button<?php echo attr($all_class);?>" onclick='top.restoreSession()'><?php echo xlt('All'); ?></a>
<a href="office_comments_full.php?offset=0&active=1" class="css_button<?php echo attr($active_class);?>" onclick='top.restoreSession()'><?php echo xlt('Only Active'); ?></a>
<a href="office_comments_full.php?offset=0&active=0" class="css_button<?php echo attr($inactive_class);?>" onclick='top.restoreSession()'><?php echo xlt('Only Inactive'); ?></a>

<input type="hidden" name="mode" value="update">
<input type="hidden" name="offset" value="<?php echo attr($offset);?>">
<input type="hidden" name="active" value="<?php echo attr($active);?>">
<br/>

<table border="0" class="existingnotes table table-striped">
<?php
//display all of the notes for the day, as well as others that are active from previous dates, up to a certain number, $N

$notes = $oNoteService->getNotes($active, $offset, $N);

$result_count = 0;
//retrieve all notes
if ($notes) {
    print "<thead><tr><th>" . xlt("Active") . "</th><th>" . xlt("Date") . " (" . xlt("Sender") . ")</th><th>" . xlt("Office Note") . "</th></tr></thead><tbody>";
foreach ($notes as $note) {
    $result_count++;

    $date = $note->getDate()->format('Y-m-d');
    $date = oeFormatShortDate($date);

    $todaysDate = new DateTime();
    if ($todaysDate->format('Y-m-d') == $date) {
        $date_string = xl("Today") . ", " . $date;
    } else {
        $date_string = $date;
    }

    if ($note->getActivity()) { $checked = "checked"; }
    else { $checked = ""; }

    print "<tr><td><input type=hidden value='' name='act".attr($note->getId())."' id='act".attr($note->getId())."'>";
    print "<input name='box".attr($note->getId())."' id='box".attr($note->getId())."' onClick='javascript:document.update_activity.act".attr($note->getId()).".value=this.checked' type=checkbox $checked></td>";
    print "<td><label for='box".attr($note->getId())."' class='bold'>".text($date_string) . "</label>";
    print " <label for='box".attr($note->getId())."' class='bold'>(". text($note->getUser()->getUsername()).")</label></td>";
    print "<td><label for='box".attr($note->getId())."' class='text'>" . nl2br(text($note->getBody())) . "&nbsp;</label></td></tr></tbody>\n";

}
}else{
//no results
print "<tr><td></td><td></td><td></td></tr>\n";
}

?>
</table>

<input type="submit" value="<?php echo xla('Save Activity'); ?>" />
</form>
<hr>
<table width="400" border="0" cellpadding="0" cellspacing="0" class="table">
<tr><td>
<?php
if ($offset>($N-1)) {
echo "<a class='css_button' href=office_comments_full.php?active=".attr($active)."&offset=".attr($offset-$N)." onclick='top.restoreSession()'>".xlt('Previous')."</a>";
}
?>
</td><td align=right>
<?php
if ($result_count == $N) {
echo "<a class='css_button' href=office_comments_full.php?active=".attr($active)."&offset=".attr($offset+$N)." onclick='top.restoreSession()'>".xlt('Next')."</a>";
}
?>
</td></tr>
</table>
</div>
</body>
</html>
