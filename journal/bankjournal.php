<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2013		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2011-2012 Alexandre Spangaro	  <alexandre.spangaro@gmail.com>
 * Copyright (C) 2013       Florian Henry	  <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/compta/journal/sellsjournal.php
 *		\ingroup    societe, facture
 *		\brief      Page with sells journal
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if ( ! $res)
		$res = @include("../../../main.inc.php"); // From "custom" directory
require_once(DOL_DOCUMENT_ROOT."/core/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';

dol_include_once('/ventilation/compta/class/comptacompte.class.php');
dol_include_once('/ventilation/compta/class/bookkeeping.class.php');

$langs->load("companies");
$langs->load("other");
$langs->load("compta");

$date_startmonth=GETPOST('date_startmonth');
$date_startday=GETPOST('date_startday');
$date_startyear=GETPOST('date_startyear');
$date_endmonth=GETPOST('date_endmonth');
$date_endday=GETPOST('date_endday');
$date_endyear=GETPOST('date_endyear');

// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;
if (! empty($conf->comptabilite->enabled)) $result=restrictedArea($user,'compta','','','resultat');
if (! empty($conf->accounting->enabled)) $result=restrictedArea($user,'accounting','','','comptarapport');

/*
 * Actions
 */

// None



/*
 * View
 */



$year_current = strftime("%Y",dol_now());
$pastmonth = strftime("%m",dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0)
{
	$pastmonth = 12;
	$pastmonthyear--;
}


$date_start=dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end=dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start=dol_get_first_day($pastmonthyear,$pastmonth,false); $date_end=dol_get_last_day($pastmonthyear,$pastmonth,false);
}



$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_PAYS);
$idpays = $p[0];

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.label as labelurl, bu.url_id";
$sql.= " FROM ";
if (! empty($_REQUEST["bid"])) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";

$result = $db->query($sql);
if ($result)
{
	$tabfac = array();
	$tabht = array();
	$tabtva = array();
	$tabttc = array();
	$tabcompany = array();

	$num = $db->num_rows($result);
   	$i=0;
   	$resligne=array();
   	while ($i < $num)
   	{
   	    $obj = $db->fetch_object($result);
   	    // les variables
   	    $cptcli = (! empty($conf->global->COMPTA_ACCOUNT_CUSTOMER))?$conf->global->COMPTA_ACCOUNT_CUSTOMER:$langs->trans("CodeNotDef");
   	    $compta_soc = (! empty($obj->code_compta))?$obj->code_compta:$cptcli;
		
		
		$compta_prod = $obj->compte;
		if (empty($compta_prod))
		{
			if($obj->product_type == 0) $compta_prod = (! empty($conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT))?$conf->global->COMPTA_PRODUCT_SOLD_ACCOUNT:$langs->trans("CodeNotDef");
			else $compta_prod = (! empty($conf->global->COMPTA_SERVICE_SOLD_ACCOUNT))?$conf->global->COMPTA_SERVICE_SOLD_ACCOUNT:$langs->trans("CodeNotDef");
		}
		$cpttva = (! empty($conf->global->COMPTA_VAT_ACCOUNT))?$conf->global->COMPTA_VAT_ACCOUNT:$langs->trans("CodeNotDef");
		$compta_tva = (! empty($obj->account_tva)?$obj->account_tva:$cpttva);

    	//la ligne facture
   		$tabfac[$obj->rowid]["date"] = $obj->df;
   		$tabfac[$obj->rowid]["ref"] = $obj->facnumber;
   		$tabfac[$obj->rowid]["type"] = $obj->type;
   		$tabfac[$obj->rowid]["fk_facturedet"] = $obj->fdid;
   		if (! isset($tabttc[$obj->rowid][$compta_soc])) $tabttc[$obj->rowid][$compta_soc]=0;
   		if (! isset($tabht[$obj->rowid][$compta_prod])) $tabht[$obj->rowid][$compta_prod]=0;
   		if (! isset($tabtva[$obj->rowid][$compta_tva])) $tabtva[$obj->rowid][$compta_tva]=0;
   		$tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
   		$tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
   		$tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
   		$tabcompany[$obj->rowid]=array('id'=>$obj->socid, 'name'=>$obj->name, 'code_client'=>$obj->code_client);

   		$i++;
   	}
}
else {
    dol_print_error($db);
}
//write bookkeeping
if (GETPOST('action') == 'writeBookKeeping')
{
	foreach ($tabfac as $key => $val)
	{
		foreach ($tabttc[$key] as $k => $mt)
		{
		    $bookkeeping = new BookKeeping($db);
		    $bookkeeping->doc_date = $val["date"];
		    $bookkeeping->doc_ref = $val["ref"];
		    $bookkeeping->doc_type = 'facture_client';
		    $bookkeeping->fk_doc = $key;
		    $bookkeeping->fk_docdet = $val["fk_facturedet"];
		    $bookkeeping->code_tiers = $tabcompany[$key]['code_client'];
		    $bookkeeping->numero_compte = $k;
		    $bookkeeping->label_compte = $tabcompany[$key]['name'];
		    $bookkeeping->montant = $mt;
		    $bookkeeping->sens = ($mt >= 0)?'D':'C';
		    $bookkeeping->debit = ($mt >= 0)?$mt:0;
		    $bookkeeping->credit = ($mt < 0)?$mt:0;

		    $bookkeeping->create();
		}
		// product
		foreach ($tabht[$key] as $k => $mt)
		{
			if ($mt)
			{
			    // get compte id and label
			    $compte = new ComptaCompte($db);
			    if ($compte->fetch(null, $k))
			    {
				    $bookkeeping = new BookKeeping($db);
				    $bookkeeping->doc_date = $val["date"];
				    $bookkeeping->doc_ref = $val["ref"];
				    $bookkeeping->doc_type = 'facture_client';
				    $bookkeeping->fk_doc = $key;
				    $bookkeeping->fk_docdet = $val["fk_facturedet"];
		    		$bookkeeping->code_tiers = '';
				    $bookkeeping->numero_compte = $k;
				    $bookkeeping->label_compte = $compte->intitule;
				    $bookkeeping->montant = $mt;
				    $bookkeeping->sens = ($mt < 0)?'D':'C';
				    $bookkeeping->debit = ($mt < 0)?$mt:0;
				    $bookkeeping->credit = ($mt >= 0)?$mt:0;

				    $bookkeeping->create();
			    }
			}
		}
		// vat
		//var_dump($tabtva);
		foreach ($tabtva[$key] as $k => $mt)
		{
		    if ($mt)
		    {
			    $bookkeeping = new BookKeeping($db);
			    $bookkeeping->doc_date = $val["date"];
			    $bookkeeping->doc_ref = $val["ref"];
			    $bookkeeping->doc_type = 'facture_client';
			    $bookkeeping->fk_doc = $key;
			    $bookkeeping->fk_docdet = $val["fk_facturedet"];
			    $bookkeeping->fk_compte = $compte->id;
	    		$bookkeeping->code_tiers = '';
			    $bookkeeping->numero_compte = $k;
			    $bookkeeping->label_compte = 'TVA';
			    $bookkeeping->montant = $mt;
			    $bookkeeping->sens = ($mt < 0)?'D':'C';
			    $bookkeeping->debit = ($mt < 0)?$mt:0;
			    $bookkeeping->credit = ($mt >= 0)?$mt:0;

			    $bookkeeping->create();
			}
		}
	}
}
// export csv
if (GETPOST('action') == 'export_csv')
{
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment;filename=journal_ventes.csv');
	foreach ($tabfac as $key => $val)
	{
	    $date = dol_print_date($db->jdate($val["date"]),'day');
		print '"'.$date.'",';
		print '"'.$val["ref"].'",';
		foreach ($tabttc[$key] as $k => $mt)
		{
			print '"'.html_entity_decode($k).'","'.$langs->trans("ThirdParty").'","'.($mt>=0?price($mt):'').'","'.($mt<0?price(-$mt):'').'"';
		}
		print "\n";
		// product
		foreach ($tabht[$key] as $k => $mt)
		{
			if ($mt)
			{
				print '"'.$date.'",';
				print '"'.$val["ref"].'",';
				print '"'.html_entity_decode($k).'","'.$langs->trans("Products").'","'.($mt<0?price(-$mt):'').'","'.($mt>=0?price($mt):'').'"';
				print "\n";
			}
		}
		// vat
		//var_dump($tabtva);
		foreach ($tabtva[$key] as $k => $mt)
		{
		    if ($mt)
		    {
				print '"'.$date.'",';
				print '"'.$val["ref"].'",';
				print '"'.html_entity_decode($k).'","'.$langs->trans("VAT").'","'.($mt<0?price(-$mt):'').'","'.($mt>=0?price($mt):'').'"';
				print "\n";
			}
		}
	}
}
else
{

$form=new Form($db);

llxHeader('',$langs->trans("BankJournal"),'');

$nom=$langs->trans("BankJournal");
$nomlink='';
$periodlink='';
$exportlink='';
$builddate=time();
$description=$langs->trans("DescSellsJournal").'<br>';
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $description.= $langs->trans("DepositsAreNotIncluded");
else  $description.= $langs->trans("DepositsAreIncluded");
$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink, array('action'=>'') );


	
	print '<input type="button" class="button" style="float: right;" value="Export CSV" onclick="launch_export();" />';
	
	print '<input type="button" class="button" value="'.$langs->trans("writeBookKeeping").'" onclick="writeBookKeeping();" />';
	
	print '
	<script type="text/javascript">
		function launch_export() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
		function writeBookKeeping() {
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("writeBookKeeping");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
	</script>';

	/*
	 * Show result array
	 */

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td>".$langs->trans("Date")."</td>";
	print "<td>".$langs->trans("Piece").' ('.$langs->trans("InvoiceRef").")</td>";
	print "<td>".$langs->trans("Account")."</td>";
	print "<td>".$langs->trans("Type")."</td><th align='right'>".$langs->trans("Debit")."</td><th align='right'>".$langs->trans("Credit")."</td>";
	print "</tr>\n";

	$var=true;
	$r='';

	$invoicestatic=new Facture($db);
	$companystatic=new Client($db);

	foreach ($tabfac as $key => $val)
	{
		$invoicestatic->id=$key;
		$invoicestatic->ref=$val["ref"];
		$invoicestatic->type=$val["type"];

	    $date = dol_print_date($db->jdate($val["date"]),'day');

		print "<tr ".$bc[$var].">";
		// third party
		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
		print "<td>".$date."</td>";
		print "<td>".$invoicestatic->getNomUrl(1)."</td>";
		foreach ($tabttc[$key] as $k => $mt)
		{
			$companystatic->id=$tabcompany[$key]['id'];
	    	$companystatic->name=$tabcompany[$key]['name'];
	    	$companystatic->client=$tabcompany[$key]['code_client'];
	    print "<td>".$k;
		print "</td><td>".$langs->trans("ThirdParty");
		print ' ('.$companystatic->getNomUrl(0,'customer',16).')';
		print "</td><td align='right'>".($mt>=0?price($mt):'')."</td><td align='right'>".($mt<0?price(-$mt):'')."</td>";
		}
		print "</tr>";
		// product
		foreach ($tabht[$key] as $k => $mt)
		{
			if ($mt)
			{
				print "<tr ".$bc[$var].">";
				//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
				print "<td>".$date."</td>";
				print "<td>".$invoicestatic->getNomUrl(1)."</td>";
				print "<td>".$k."</td><td>".$val["compte"]."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
			}
		}
		// vat
		//var_dump($tabtva);
		foreach ($tabtva[$key] as $k => $mt)
		{
		    if ($mt)
		    {
	    		print "<tr ".$bc[$var].">";
	    		//print "<td>".$conf->global->COMPTA_JOURNAL_SELL."</td>";
				print "<td>".$date."</td>";
	    		print "<td>".$invoicestatic->getNomUrl(1)."</td>";
	    		print "<td>".$k."</td><td>".$langs->trans("VAT")."</td><td align='right'>".($mt<0?price(-$mt):'')."</td><td align='right'>".($mt>=0?price($mt):'')."</td></tr>";
		    }
		}

		$var = !$var;
	}

	print "</table>";


	// End of page
	llxFooter();
}
$db->close();
?>