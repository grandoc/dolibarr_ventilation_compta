<?php
/* Copyright (C) 2013      Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013      Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	  \file       accountingex/core/lib/account.lib.php
 *	  \ingroup    Accounting Expert
 *		\brief      Ensemble de fonctions de base pour les comptes comptables
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function account_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/accountingex/admin/fiche.php',1).'?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'accounting');

	/*
  $head[$h][0] = DOL_URL_ROOT.'/accountingex/admin/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h++;
  */
	$head[$h][0] = dol_buildpath('/accountingex/admin/info.php',1).'?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;
	
	complete_head_from_modules($conf,$langs,$object,$head,$h,'accounting','remove');

	return $head;
}

/**
 * Account desactivate
 *
 * @param User $user update
 * @return int if KO, >0 if OK
 */
function account_desactivate($user) {

global $langs;
		
$result = $this->checkUsage ();
		
if ($result > 0) {
  $this->db->begin ();
			
  $sql = "UPDATE " . MAIN_DB_PREFIX . "accountingaccount ";
	$sql .= "SET active = '0'";
	$sql .= " WHERE rowid = " . $this->id;
			
	dol_syslog ( get_class ( $this ) . "::desactivate sql=" . $sql, LOG_DEBUG );
	$result = $this->db->query ( $sql );
	
  if ($result) {
	   $this->db->commit ();
		 return 1;
	} else {
	   $this->error = $this->db->lasterror ();
		 $this->db->rollback ();
		 return - 1;
	}
} else {
    return - 1;
	}
}

/**
 * Account activate
 *
 * @param User $user update
 * @return int if KO, >0 if OK
 */
function account_activate($user) {

global $langs;
		
$this->db->begin ();
		
$sql = "UPDATE " . MAIN_DB_PREFIX . "accountingaccount ";
$sql .= "SET active = '1'";
$sql .= " WHERE rowid = " . $this->id;
		
dol_syslog ( get_class ( $this ) . "::activate sql=" . $sql, LOG_DEBUG );
$result = $this->db->query ( $sql );
if ($result) {
  $this->db->commit ();
  return 1;
} else {
  $this->error = $this->db->lasterror ();
	$this->db->rollback ();
	return - 1;
	}
}

/**
 *	Return general account with defined length
 *
 * 	@param $account   					
 *
 *	@return $account
 */
function length_accountg($account)
{
	global $conf,$langs;
  
  $g = $conf->global->ACCOUNTINGEX_LENGTH_GACCOUNT;
  
  if (! empty($g))
  {
    // Clean parameters
  	$i = strlen($account);
    
    while ($i < $g)
    {
      $account .= '0';
        
      $i++;
    }
    
    return $account;
  }
  else
  { 
	  return $account;
  }
}

/**
 *	Return auxiliary account with defined length
 *
 * 	@param $account   					
 *
 *	@return $account
 */
function length_accounta($accounta)
{
	global $conf,$langs;
  
  $a = $conf->global->ACCOUNTINGEX_LENGTH_AACCOUNT;
  
  if (! empty($a))
  {
    // Clean parameters
  	$i = strlen($accounta);
    
    while ($i < $a)
    {
      $accounta .= '0';
        
      $i++;
    }
    
    return $accounta;
  }
  else
  { 
	  return $accounta;
  }
}

?>