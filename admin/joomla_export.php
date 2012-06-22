<?php
require ('admin_header.php');
	require ('includes/settings.php');
	date_default_timezone_set('Europe/Berlin');

	$db_host = $DBhost;
	$db_user = $DBuser;
	$db_pwd = $DBpass;
	$database = $DBName;


	if (!mysql_connect($db_host, $db_user, $db_pwd))
	    die("Can't connect to database");

	if (!mysql_select_db($database))
	    die("Can't select database");
    mysql_query("set names 'utf8';");
	// sending query
	$result = mysql_query("SELECT 
		/*	SHA1( u1.password ) AS vpncode, */
			u1.email,
				DATE(u1.registerDate) AS registrierungsdatum,
				j1.confirmed AS email_confirmed,
				 u1.block,
				j1.cb_geschlecht,
				 j1.cb_alter,
				j1.cb_deutsch,
				 j1.cb_bart ,
				 j1.cb_adlershofbesuch,
				
				 j1.cb_studienende,
				j1.cb_welchesstudium,
				 j1.cb_studiumfrei,
				 j1.cb_wostudium,
				 j1.cb_wowohnen,
				 j1.cb_uni,
				j1.cb_angemeldet,
				j1.cb_seitanmeldung,
				
				 j1.cb_abschluss,
				 j1.cb_studienende,
				 j1.cb_berufseintritt,
				 j1.cb_bereitsjob,
				j1.cb_stelleinaussicht,
				 j1.cb_auslandsplan,
				 j1.cb_wegausberlin,
				
				 j1.cb_nummer,
				j1.cb_geworben,
				(
					
					(j1.confirmed  = 1) + 
					(j1.cb_bart!='nein') + 
					(j1.cb_deutsch!='nein') + 
					(j1.cb_adlershofbesuch = 'ja') + 
					(j1.cb_studienende != 'später' AND j1.cb_studienende != 'bin bereits fertig') + 
					(j1.cb_studiumfrei != 'Psychologie') + 
					(j1.cb_abschluss != 'Promotion') + 
					(j1.cb_berufseintritt != 'später' AND j1.cb_berufseintritt != 'in 12 Monaten') + 
					((j1.cb_berufseintritt != 'arbeite bereits' OR j1.cb_bereitsjob = 'nein, werde in meinem jetzigen Job nicht bleiben')) +
					(j1.cb_bereitsjob != 'ja, bleibe in meinem jetzigen Job') +
					((j1.cb_auslandsplan = 'nein' OR  j1.cb_auslandsplan = 'weiß es noch nicht')) + 
					(j1.cb_wegausberlin != 'ganz sicher') + 
					(j1.cb_wostudium != 'anderswo') + 
					(j1.cb_wowohnen != 'anderswo')
				) AS geeignetkeit_alt,
				(
				(j1.confirmed  = 1)  +
				(u1.block = 1)  +
				(j1.cb_bart!='nein')  +
				(j1.cb_fliessenddeutsch = 'ja')  +
				(j1.cb_adlershofbesuch = 'ja')  +
				((j1.cb_studienende != 'später' AND j1.cb_studienende != 'bin bereits fertig'))  +
				(j1.cb_studiumfrei NOT LIKE '%Psychologie%')  +
				(j1.cb_abschluss != 'Promotion')  +
				(j1.cb_berufseintritt != 'später')  +
				(j1.cb_berufseintritt != 'in 12 Monaten')  +
				(j1.cb_berufseintritt != 'bleibe in meinem jetzigen Job')  +
				(j1.cb_bereitsjob != 'ja, bleibe in meinem jetzigen Job')  +
				(j1.cb_bereitsjob != 'ja, übergangsweise, bis ich eine Stelle gefunden habe')  +
				((j1.cb_auslandsplan = 'nein' OR  j1.cb_auslandsplan = 'weiß es noch nicht'))  +
				(j1.cb_wegausberlin != 'ganz sicher')  +
				(j1.cb_wohnenoderstudieren  != 'anderswo')
				) AS geeignetkeit_neu
				FROM
	jos_users AS u1
	LEFT JOIN jos_comprofiler AS j1 ON u1.id=j1.user_id
	WHERE u1.usertype = 'Registriert'");
	if (!$result) {
	    die("Query to show fields from table failed".mysql_error());
	}

	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=$table".date('YmdHis').".csv");
	header("Pragma: no-cache");
	header("Expires: 0");


	$fields_num = mysql_num_fields($result);

	// printing table headers
	for($i=0; $i<$fields_num; $i++)
	{
	    $field = mysql_fetch_field($result);
		$fieldname = $field->name;
	    if($i!=($fields_num-1)) echo $fieldname."\t";
	    else echo $fieldname;
	}
	echo "\n";

	// printing table rows
	while($row = mysql_fetch_row($result))
	{

	    // $row is array... foreach( .. ) puts every element
	    // of $row to $cell variable
		$rowwidth = count($row);
	    for($i=0;$i<$rowwidth;$i++) {
			$row[$i] = preg_replace("/[\r\n|\r|\n]/","\\n",$row[$i]);
			$row[$i] = str_replace("\t","    ",$row[$i]);
	        if($i!=($rowwidth-1)) echo "{$row[$i]}\t";
			else echo "{$row[$i]}";
		}

	    echo "\n";
	}
	mysql_free_result($result);

	?>