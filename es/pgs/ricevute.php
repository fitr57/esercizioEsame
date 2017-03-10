<?php 
error_reporting (E_ALL);
ini_set('display_errors',1);
$msg='';
	$tbl='ricevute';
	$id = (!empty($_REQUEST['id'])) ? intval($_REQUEST['id']) : false;
	$record=(empty($_REQUEST['id'])) ?  R::dispense($tbl) : R::load($tbl, intval($_REQUEST['id']));
	if (!empty($_POST['clienti_id'])) :
			foreach ($_POST as $key=>$value){
				$record[$key]=$value;
			}
		try {
			R::store($record);
			//$msg='Dati salvati correttamente ('.json_encode($record).') ';
		} catch (RedBeanPHP\RedException\SQL $e) {
			$msg=$e->getMessage();
		}
	endif;	
	
	if (!empty($_REQUEST['del'])) : 
		$record=R::load($tbl, intval($_REQUEST['del']));
		try{
			R::trash($record);
		} catch (RedBeanPHP\RedException\SQL $e) {
			$msg=$e->getMessage();
		}
	endif;
	
	$data=R::findAll($tbl, 'ORDER by id ASC LIMIT 999');
	$clienti=R::findAll('clienti');
	$new=!empty($_REQUEST['create']);
	
	$somma=R::getCell('select SUM(importo) from ricevute');

	
?>

<h1>
	<a href="index.php">
		<?=($id) ? ($new) ? 'Nuova ricevuta' : 'Ricevuta n. '.$id : 'Ricevute';?>
	</a>
</h1>
<?php if ($id || $new) : ?>
		<form method="post" action="?p=<?=$tbl?>">
			<?php if ($id) : ?>
				<input type="hidden" name="id" value="<?=$record->id?>" />
			<?php endif; ?>

			<label for="dataemissione">
				Data
			</label>
			<?php
			$today=date('Y-m-d');
			?> 
			
			<!-- PER VALIDARE IL CONTROLLO SULLA DATA FUTURA BISOGNA PASSARE DENTRO VALUE LA VARIABILE TODAY 
			CHE VIENE INIZIALIZZATA SOPRA CON LA FUNZIONE DATE -->
				<input name="dataemissione"  value="<?=$today?>" type="date" max="<?=$today?>"/>
			
			<label for="clienti_id">
				Cliente
			</label>
			<select name="clienti_id">
				<option />
				<?php foreach ($clienti as $opt) : ?>
					<option value="<?=$opt->id?>" <?=($opt->id==$id) ? 'selected' :'' ?> >
						<?=$opt->nome?>
					</option>
				<?php endforeach; ?>
			</select>
			
			<label for="numero_ric">
				N ricevuta
			</label>
			<input name="ricevuta"  value="<?=$record->numero_ric?>" autofocus required  />	
			
			
			
			
			<label for="descrizione">
				Descrizione
			</label>
			<input name="descrizione"  value="<?=$record->descrizione?>" autofocus required  />			
			
			<label for="importo">
				Importo
			</label>			
			<input name="importo" value="<?=$record->importo?>"  required/>
			<button type="submit" tabindex="-1">
				Salva
			</button>
			
			<a href="?p=<?=$tbl?>" >
				Elenco
			</a>			
			
			<a href="?p=<?=$tbl?>&del=<?=$ma['id']?>" tabindex="-1">
				Elimina
			</a>					
		</form>
<?php else : ?>




<button class="btn btn-info" id="intervallo">Filtro Date</button> 
</br>
</br>


	<div class="tablecontainer">
		<table id="ema"  class="table table-bordered table-striped responsive">
			<colgroup>
				<col style="width:150px" />
			</colgroup>
			<thead>
				<tr>
					<th>Cliente</th>
					<th>Data</th>
					<th>Descrizione</th>
					<th>Importo</th>
					<th>numero ricevuta</th>
					<th style="width:60px;text-align:center">Modifica</th>
					<th style="width:60px;text-align:center">Cancella</th>
				</tr>
			</thead>
			 <tfoot>
                <tr>
                    <th colspan="4" style="text-align:right">Total:</th>
                    <th></th>
                </tr>
            </tfoot>
		

			<tbody>
			<?php foreach ($data as $r) : ?>
				<tr>
					<td>
							<?=($r->clienti_id) ? $r->clienti->nome : ''?>
					</td>			
					<td>
						<?=date('d/m/Y',strtotime($r->dataemissione))?>
					</td>
					<td>
						<?=$r->descrizione?>
					</td>
					<td style="text-align:right" >
						<?= sprintf($r->importo) ?>
					</td>			
					<td>
						<?=$r->numero_ric?>
					</td>
					<td style="text-align:center" >
					<button type="button" class="btn btn-warning">
						<a href="?p=<?=$tbl?>&id=<?=$r['id']?>">
							Mod.
						</a>
					</button>
					</td>
					<td style="text-align:center" >
						<button type="button" class="btn btn-danger">
						<a href="?p=<?=$tbl?>&del=<?=$r['id']?>" tabindex="-1">DELETE</a></button>
					</td>							
				</tr>
			<?php endforeach; ?>
	        
			</tbody>
		</table>
		
		<label>l'importo è:</label>
		<h1> <?php echo $somma;  ?></h1>
		
		<label>l'importo filtrato è:</label>
		<h2 id="fig"></h2>
		
		
		<h4 class="msg">
			<?=$msg?>
		</h4>	
	</div>
<?php endif; ?>
<a href="?p=<?=$tbl?>&create=1">Inserisci nuovo</a>
<script src="https://code.jquery.com/jquery-3.1.1.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>

<script>
	var chg=function(e){
		console.log(e.name,e.value)
		document.forms.frm.elements[e.name].value=(e.value) ? e.value : null
	}
	
$(document).ready(function() {
//DATATABLE
//metto alla variabile otable la mia tabella che ho creato
var oTable=$('#ema').dataTable({

 "footerCallback": function (row, data, start, end, display) {
                var api = this.api(), data;

                // Remove the formatting to get integer data for summation
                var intVal = function (i) {
                    return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ?
                            i : 0;
                };

                // Total over all pages
               	total = api
                        .column(3)
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                // Total over this page
                pageTotal = api
                        .column(3, {page: 'current'})
                        .data()
                        .reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                // Update footer
                $(api.column(3).footer()).html(
                        '€' + pageTotal + 'Totale della pagina ( €' + total + ' Totale Generale)'
                        );
            }
        });


//DATE RANGE


var startdate;
var enddate;
//prendo il mio input e ci metto il datepicker

$('#intervallo').daterangepicker({
format: 'DD/MM/YYYY',
  autoUpdateInput: false,
      locale: {
          cancelLabel: 'Clear'
      }

},
function(start, end,label) {

//grazie alla libreria moment converto in stringa le due date fornite con il date picker

var s = moment(start.toISOString());
var e = moment(end.toISOString());
startdate = s.format("YYYY-MM-DD");
enddate = e.format("YYYY-MM-DD");
});
//creazione del filtro con i vari id

$('#intervallo').on('apply.daterangepicker', function(ev, picker) {
startdate=picker.startDate.format('YYYY-MM-DD');
enddate=picker.endDate.format('YYYY-MM-DD');
oTable.fnDraw();
});

$.fn.dataTableExt.afnFiltering.push(
function( oSettings, aData, iDataIndex ) {
if(startdate!=undefined){

//indice della colonna dove si trovano le date nel mio caso 1
//e conversione successiva nel formato dd-mm-yyyy

var pippo= aData[3].value;
var coldate = aData[1].split("/");
var d = new Date(coldate[2], coldate[1]-1 , coldate[0]);
var date = moment(d.toISOString());
date =    date.format("YYYY-MM-DD");
document.getElementById("fig").innerHTML = pippo;


dateMin=startdate.replace(/-/g, "");
dateMax=enddate.replace(/-/g, "");
date=date.replace(/-/g, "");

//console.log(dateMin, dateMax, date);

if ( dateMin == "" && date <= dateMax){
return true;
}
else if ( dateMin =="" && date <= dateMax ){
return true;
}
else if ( dateMin <= date && "" == dateMax ){
return true;
}
else if ( dateMin <= date && date <= dateMax ){
return true;
}

return false;
}
}
);
} );
</script>


