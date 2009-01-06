<?php
/**
 * $Horde: incubator/operator/search.php,v 1.10 2009/01/06 17:51:06 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('OPERATOR_BASE', dirname(__FILE__));
require_once OPERATOR_BASE . '/lib/base.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';
require_once OPERATOR_BASE . '/lib/Form/SearchCDR.php';

$renderer = new Horde_Form_Renderer();
$vars = Variables::getDefaultVariables();

if (!$vars->exists('rowstart')) {
    $rowstart = 0;
} elseif (!is_numeric($rowstart = $vars->get('rowstart'))) {
    $notification->push(_("Invalid number for row start.  Using 0."));
    $rowstart = 0;
}

$numrows = $prefs->getValue('resultlimit');
if (!is_numeric($numrows)) {
    $notification->push(_("Invalid number for rows for search limit.  Using 100."));
    $numrows = 100;
}

$form = new SearchCDRForm(_("Search Call Detail Records"), $vars);
if ($form->isSubmitted() && $form->validate($vars, true)) {
    $accountcode = $vars->get('accountcode');
    $dcontext = $vars->get('dcontext');
    if (empty($dcontext)) {
        $dcontext = '%';
    }
    $start = new Horde_Date($vars->get('startdate'));

    $end = new Horde_Date($vars->get('enddate'));
    if (is_a($start, 'PEAR_Error') || is_a($end, 'PEAR_Error')) {
        $notification->push(_("Invalid date requested."));
    } else {
        $data = $operator_driver->getRecords($start, $end, $accountcode,
                                             $dcontext, $rowstart, $numrows);
        if (is_a($data, 'PEAR_Error')) {
            $notification->push($data);
            $data = array();
        }
        $_SESSION['operator']['lastsearch']['params'] = array(
            'accountcode' => $vars->get('accountcode'),
            'dcontext' => $vars->get('dcontext'),
            'startdate' => $vars->get('startdate'),
            'enddate' => $vars->get('enddate'));
    }
} else {
    if (isset($_SESSION['operator']['lastsearch']['params'])) {
        foreach($_SESSION['operator']['lastsearch']['params'] as $var => $val) {
            $vars->set($var, $val);
        }
    }
}

$title = _("Search Call Detail Records");
Horde::addScriptFile('stripe.js', 'horde', true);

require OPERATOR_TEMPLATES . '/common-header.inc';
require OPERATOR_TEMPLATES . '/menu.inc';

$form->renderActive($renderer, $vars);

$columns = unserialize($prefs->getValue('columns'));
if (!empty($data)) {
    require OPERATOR_TEMPLATES . '/search/header.inc';
    unset($data['count'], $data['minutes'], $data['failed']);
    foreach ($data as $record) {
        require OPERATOR_TEMPLATES . '/search/row.inc';
    }
    require OPERATOR_TEMPLATES . '/search/footer.inc';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
