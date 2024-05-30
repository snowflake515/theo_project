<?php
$sql1 = "DELETE FROM Wellness_DataArchive.dbo.PWHistory

PRINT 'Setting up temp tables...'
USE Wellness_eCastEMR_Data
DROP Table Wellness_eCastEMR_Data.dbo.tempPWTBots
SET ANSI_NULLS ON
SET QUOTED_IDENTIFIER ON
SET ANSI_PADDING ON
CREATE TABLE Wellness_eCastEMR_Data.[dbo].[tempPWTBots](
	[tempPWTBots_ID] [int] IDENTITY(1,1) NOT NULL,
	[TBot] varchar(10) )
CREATE INDEX tempPWTBots_idx
  ON Wellness_eCastEMR_Data.dbo.tempPWTBots(TBot)

PRINT 'Got the Encounter_ID and now filling up temp table...'
SET ANSI_PADDING OFF

DECLARE @Patient_ID INT, @Encounter_ID INT, @n INT, @max INT, 
        @PWCategory Varchar(50), @PWService Varchar(50), @PWCode Varchar(10),@PWBenefit Varchar(50),@PWNeeded SmallInt,
		@PWMaster_ID INT, @TDate DATE, @PWSortOrder SMALLINT
SELECT @Encounter_ID	= $PrimaryKey
SELECT @Patient_ID		= $PatientKey

PRINT 'Setting PWHistory flags...'
UPDATE Wellness_DataArchive.dbo.PWHistory
SET Hidden		= 1 WHERE 
Patient_ID		= @Patient_ID AND 
Encounter_ID	= @Encounter_ID

SELECT @TDate	= GetDate()  

PRINT 'Inserting first 4 rows of PWHistory...'
SELECT * FROM Wellness_DataArchive.dbo.PWHistory
DELETE FROM Wellness_DataArchive.dbo.PWHistory
INSERT INTO Wellness_DataArchive.dbo.PWHistory
(PWMaster_ID, Patient_ID, Encounter_ID, PWDate, PWNeeded, PWValue, SortOrder, Hidden)
VALUES
(1,@Patient_ID,@Encounter_ID,@TDate,0,'Weight:    ',10,0),
(2,@Patient_ID,@Encounter_ID,@TDate,0,'Height:    ',20,0),
(3,@Patient_ID,@Encounter_ID,@TDate,0,'Systolic:  ',30,0),
(4,@Patient_ID,@Encounter_ID,@TDate,0,'Diastolic: ',40,0)
PRINT 'Now updating first 4 rows of PWHistory with correct vitals values...'
--- Weight
UPDATE PWH
SET PWH.PWValue = E3I.ETL3Input
FROM Wellness_DataArchive.dbo.PWHistory PWH
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL3Input E3I ON PWH.Encounter_ID = E3I.Encounter_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML3 T3 ON E3I.TML3_ID = T3.TML3_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML2 T2 ON T3.TML2_ID = T2.TML2_ID
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL1 E1 ON T2.TML1_ID = E1.TML1_ID
WHERE PWH.PWMaster_ID = 1
AND E1.Encounter_ID = @Encounter_ID
AND T2.TML2_HeaderMaster_ID = 33
AND T3.TML3_TBotMaster_ID = 424;
--- Height
UPDATE PWH
SET PWH.PWValue = E3I.ETL3Input
FROM Wellness_DataArchive.dbo.PWHistory PWH
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL3Input E3I ON PWH.Encounter_ID = E3I.Encounter_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML3 T3 ON E3I.TML3_ID = T3.TML3_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML2 T2 ON T3.TML2_ID = T2.TML2_ID
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL1 E1 ON T2.TML1_ID = E1.TML1_ID
WHERE PWH.PWMaster_ID = 2
AND E1.Encounter_ID = @Encounter_ID
AND T2.TML2_HeaderMaster_ID = 33
AND T3.TML3_TBotMaster_ID = 423;
--- Systolic
UPDATE PWH
SET PWH.PWValue = E3I.ETL3Input
FROM Wellness_DataArchive.dbo.PWHistory PWH
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL3Input E3I ON PWH.Encounter_ID = E3I.Encounter_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML3 T3 ON E3I.TML3_ID = T3.TML3_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML2 T2 ON T3.TML2_ID = T2.TML2_ID
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL1 E1 ON T2.TML1_ID = E1.TML1_ID
WHERE PWH.PWMaster_ID = 3
AND E1.Encounter_ID = @Encounter_ID
AND T2.TML2_HeaderMaster_ID = 33
AND T3.TML3_TBotMaster_ID = 425;
--- Diastolic
UPDATE PWH
SET PWH.PWValue = E3I.ETL3Input
FROM Wellness_DataArchive.dbo.PWHistory PWH
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL3Input E3I ON PWH.Encounter_ID = E3I.Encounter_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML3 T3 ON E3I.TML3_ID = T3.TML3_ID
INNER JOIN Wellness_eCastEMR_Template.dbo.TML2 T2 ON T3.TML2_ID = T2.TML2_ID
INNER JOIN Wellness_eCastEMR_Data.dbo.ETL1 E1 ON T2.TML1_ID = E1.TML1_ID
WHERE PWH.PWMaster_ID = 4
AND E1.Encounter_ID = @Encounter_ID
AND T2.TML2_HeaderMaster_ID = 33
AND T3.TML3_TBotMaster_ID = 426;


/* Mow insert rows into tempPWTBots */
PRINT 'Setting values in tempPWTBots...'
INSERT INTO Wellness_eCastEMR_Data.[dbo].[tempPWTBots]
SELECT CONCAT(TM.TML3_TBotMaster_ID,'-',TM.TML3_TBotData) FROM Wellness_eCastEMR_Data.dbo.ETL3 ET
JOIN Wellness_eCastEMR_Template.dbo.TML3 TM ON ET.TML3_ID = TM.TML3_ID
JOIN eCastMaster.dbo.TBotMaster TB ON TM.TML3_TBotMaster_ID = TB.TBotMaster_ID
WHERE ET.Encounter_ID	= @Encounter_ID

PRINT 'Starting the loop through PWMaster...'
SELECT @n = 5  
SELECT @max = COUNT(*) FROM Wellness_DataArchive.dbo.PWMaster
WHILE @n <= @max
  BEGIN
    PRINT 'Looping through PWMaster...'
	  SELECT  
	    @PWMaster_ID		= PWMaster_ID,
		@PWCategory			= Category,
		@PWService			= Service,
		@PWCode 			= Code,
		@PWBenefit			= Benefit,
		@PWNeeded			= 0,
		@PWSortOrder		= SortOrder
		FROM Wellness_DataArchive.dbo.PWMaster WHERE PWMaster_ID = @n
	    SELECT TBot FROM Wellness_DataArchive.dbo.PWMasterTBots TB WHERE TB.PWMaster_ID = @n
        INTERSECT
        SELECT TBot FROM Wellness_eCastEMR_Data.dbo.TempPWTBots TTB
		IF @@ROWCOUNT <> 0 
		  BEGIN 
		    SELECT @PWNeeded = 1
		  END
      INSERT INTO Wellness_DataArchive.dbo.PWHistory
      (PWMaster_ID,Patient_ID,Encounter_ID,PWDate,PWNeeded,PWValue,SortOrder,Hidden)
      VALUES
      (@PWMaster_ID,@Patient_ID,@Encounter_ID,@TDate,@PWNeeded,'',@PWSortOrder,0)
      SELECT @n = @n + 1 
      END
    ";

$sql = "	SELECT PWM.PWMaster_ID,PWM.Category,PWM.Service,PWM.Code,PWM.Benefit,PWH.PWValue,PWH.PWNeeded,PWM.SortOrder
FROM Wellness_DataArchive.dbo.PWHistory PWH
JOIN Wellness_DataArchive.dbo.PWMaster PWM
ON PWH.PWMaster_ID = PWM.PWMaster_ID
WHERE Patient_ID = $PatientKey AND Encounter_ID = $PrimaryKey AND
(PWH.Hidden IS NULL or PWH.Hidden = 0)
ORDER BY PWH.SortOrder";

$sql2 = "SELECT DOB FROM Wellness_eCastEMR_Data.dbo.PatientProfile where Patient_ID = $PatientKey";

  $res = $this->ReportModel->data_db->query($sql1);
  $this->ReportModel->data_db->close();
  $getAWACSScreening = $this->ReportModel->data_db->query($sql);


  log_message('error', "ididid==>>>");
  log_message('error', $PatientKey);
  log_message('error', $PrimaryKey);

// $res = $this->db->query($sql1, false);
// $result = odbc_exec($connection, $query);
// odbc_free_result();
// $res = $this->db->query($sql, false);
// // $res = $this->db->query($sql1 . $sql, TRUE);
// log_message('error', "res");
// // log_message('error', $res);
// $methods = get_class_methods($getAWACSScreening);

// foreach ($methods as $method) {
//   log_message("error", $method);
//   log_message('error', json_encode($getAWACSScreening->$method()));
// }

// $getAWACSScreening = $this->ReportModel->data_db->query($sql1 . $sql);

// $methods = get_class_methods($res);

// foreach ($methods as $method) {
//   log_message("error", $method);
//   log_message('error', json_encode($res->$method()));
  
// }

$getAWACSScreening_num = $getAWACSScreening->num_rows();
$getAWACSScreening_result = $getAWACSScreening->result();
$this->ReportModel->data_db->close();

$dob = $this->ReportModel->data_db->query($sql2);
$dob_result = $dob->row();

if ($getAWACSScreening_num != 0) {

  $data['HeaderKey'] = $HeaderKey;
  $data['PatientKey'] = $PatientKey;
  $data['HeaderMasterKey'] = $HeaderMasterKey;
  $data['FreeTextKey'] = $FreeTextKey;
  $data['SOHeaders'] = $SOHeaders;
  
  $data['data_db'] = $data_db;
  $BodyFontInfo = getBodyFontInfo($data, $HeaderKey);
  $DefaultStyle = "color: #" . $BodyFontInfo['FontColor'] . "; font-size: 14  " .  "px; font-weight: " . $BodyFontInfo['FontWeight'] . "; font-family: " . $BodyFontInfo['FontFace'] . "; font-style: " . $BodyFontInfo['FontStyle'] . "; text-decoration: " . $BodyFontInfo['FontDecoration'] . ";";
  $ColumnHeaderStyle = "color: #" . $BodyFontInfo['FontColor'] . "; font-size: 14 " .  "px; font-weight: bold; font-family: " . $BodyFontInfo['FontFace'] . "; font-style: " . $BodyFontInfo['FontStyle'] . "; text-decoration: " . $BodyFontInfo['FontDecoration'] . ";";
  ?>
  <table cellpadding="0" cellspacing="0" style="width: 7.0in;">
    <tr>
      <td width="7">&nbsp;</td>
    <cfoutput>
      </tr>
      <tr>
        <td>
          <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 20px; width: 6.75in; border-style:solid; border-collapse:collapse; border-width:1px; border-top: none; border-left: none; border-right: none; border-color: #999999; border-spacing:2px;">
              <tr>
                <td nowrap align="left" colspan="4" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                  Your Key Vital Signs
                </td>
              </tr>
              <tr style="width: 22px;">
                <td nowrap align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; width: 25%; border-width:1px; border-bottom: none; border-right: none; height: 22px; padding:2px;" valign="top">
                  Age: &nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; width: 20%; text-decoration: underline; border-right: none; height: 22px; border-left: none; border-bottom: none; padding:2px;" valign="top">
                <?php echo dob_to_age($dob_result->DOB)?>
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px;  border-right: none; height: 22px; width: 18%; border-left: none; border-bottom: none; padding:2px;" valign="top">
                  Blood Pressure: &nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; text-decoration: underline; border-bottom: none; height: 22px; border-left: none; padding:2px;" valign="top">
                  <?php echo $getAWACSScreening_result[2]->PWValue; ?>&nbsp;,<?php echo $getAWACSScreening_result[3]->PWValue; ?>
                </td>
              </tr>
              <tr>
                <td nowrap align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; width: 25%;  border-width:1px; border-bottom: none; border-top: none; border-right: none; padding:2px;" valign="center">
                  Weight: &nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; text-decoration: underline; width: 20%; border-width:0px; none;padding:2px;" valign="top">
                  <?php echo $getAWACSScreening_result[0]->PWValue; ?>&nbsp;lbs
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:0px; width: 17%; none;padding:2px;" valign="top">
                  
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-bottom: none; border-top: none; border-left: none; padding:2px;" valign="top">
                  
                </td>
              </tr>
              <tr>
                <td nowrap align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; width: 25%;  border-width:1px; border-bottom: none; border-top: none; border-right: none;padding:2px;" valign="center">
                  Height: &nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; text-decoration: underline; width: 20%; border-width:0px; none;padding:2px;" valign="top">
                  <?php echo $getAWACSScreening_result[1]->PWValue; ?>&nbsp;inches
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:0px; width: 17%; none;padding:2px;" valign="top">
                  
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-bottom: none; border-top: none; border-left: none; padding:2px;" valign="top">
                  
                </td>
              </tr>
              <tr>
                <td nowrap align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; width: 25%;  border-width:1px; border-bottom: none; border-top: none; border-right: none;padding:2px;" valign="center">
                  Body Mass Index (BMI): &nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; text-decoration: underline; width: 20%; border-width:0px; none;padding:2px;" valign="top">
                  <?php echo intval($getAWACSScreening_result[0]->PWValue / ($getAWACSScreening_result[1]->PWValue * $getAWACSScreening_result[1]->PWValue) * 703); ?>&nbsp;
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:0px; width: 17%; none;padding:2px;" valign="top">
                  
                </td>
                <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-bottom: none; border-top: none; border-left: none; padding:2px;" valign="top">
                  
                </td>
              </tr>
          </table>
          <?php foreach ($getAWACSScreening_result as $index => $val) { ?>
            <?php if ($index == 4) { ?>
              <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 10px; width: 6.75in; border-style:solid; border-collapse:collapse; border-width:1px; border-top: none; border-left: none; border-right: none; border-color: #999999; border-spacing:2px;">
                <tr>
                  <td nowrap align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Medlcare Recommended
                  </td>
                  <td align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Preventive Services 
                  </td>
                  <td align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Code
                  </td>
                  <td align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Your Benefit/Guldellnes
                  </td>
                  <td align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Needed
                  </td>
                  <td align="left" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    This year*
                  </td>
                </tr>
            <?php } ?>
            <?php if ($index == 15) { ?>
              <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 10px; width: 6.75in; border-style:solid; border-collapse:collapse; border-width:1px; border-top: none; border-left: none; border-right: none; border-color: #999999; border-spacing:2px;">
                <tr>
                  <td nowrap align="left" colspan="4" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Social/Behavioral Screenings
                  </td>
                </tr>
            <?php } ?>
            <?php if ($index == 20) { ?>
              <table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 10px; width: 6.75in; border-style:solid; border-collapse:collapse; border-width:1px; border-top: none; border-left: none; border-right: none; border-color: #999999; border-spacing:2px;">
                <tr>
                  <td nowrap align="left" colspan="4" style="<?php echo $ColumnHeaderStyle; ?> border-style:solid; border-width:0px; border-left: none; border-right: none; padding:2px; color: #35A7CF" valign="top">
                    Your Additional Risk Factors
                  </td>
                </tr>
            <?php } ?>
               <?php if ($index >= 4) { ?>
                <tr>
                  <td nowrap align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-right: none;padding:2px;" valign="center">
                    <?php echo $val->Category; ?>&nbsp;
                  </td>
                  <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-left: none; border-right: none;padding:2px;" valign="center">
                    <?php echo $val->Service; ?>&nbsp;
                  </td>
                  <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-left: none; border-right: none;padding:2px;" valign="center">
                    <?php echo $val->Code; ?>&nbsp;
                  </td>
                  <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-left: none; border-right: none;padding:2px;" valign="center">
                    <?php echo $val->Benefit; ?>&nbsp;
                  </td>
                  <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-left: none; border-right: none; padding:2px; width: 59px;" valign="center">
                    <?php 
                    if ($val->PWNeeded == 1) {
                      echo '<label>Yes<input type="checkbox" class="checkBox" style="width: 20px; height: 20px;" onclick="return false" checked></label>';
                    }else{
                      echo '<label>Yes<input type="checkbox" class="checkBox" style="width: 20px; height: 20px;" onclick="return false"></label>';
                    }
                    ?>&nbsp;
                  </td>
                  <td align="left" style="<?php echo $DefaultStyle; ?> border-style:solid; border-width:1px; border-left: none; padding:2px; width: 55px;" valign="center">
                    <?php 
                    if ($val->PWNeeded == 0) {
                      echo 'No <label><input type="checkbox" class="checkBox" style="width: 20px; height: 20px;" onclick="return false" checked></label>';
                    }else{
                      echo 'No <label><input type="checkbox" class="checkBox" style="width: 20px; height: 20px;" onclick="return false"></label>';
                    }
                    ?>&nbsp;
                  </td>
                </tr>
              <?php } ?>
            <?php if ($index == 14 || $index == 19 || $index == 23) { ?>
            </table>
            <?php } ?>
            
          <?php } ?>
          <?php
    }
?>
