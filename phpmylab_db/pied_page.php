<form name="clock" onSubmit="0">
	<table id="footer">
		<tr>
			<td>
				<?php
					echo '<p><a href="mailto:'.$web_adress.'" title="Envoyer un mel aux Webmestres">Webmestres</a> [ <a href="'.$lien_organisme.'" target="_blank" title="Site Web '.$organisme.'">'.$organisme.'</a> ]</p>';
				?>
			</td>
			<td class="dateHeure">
				<?php
					echo ucwords(strftime("%A %d %B %Y")).'<input type="text" id="dateCourante" size="8" />';;
				?>
			</td>
		</tr>
	</table>
</form>
</div>
<div id="version2">
	<table>
		<tr>
			<td>
				<?php
					echo '<p>PhpMyLab v'.$version.' &copy; 2015 - <a href="http://phpmylab.in2p3.fr/cnil.php" target="_blank">Mentions CNIL</a></p>';
				?>
			</td>
		</tr>
	</table>
</div>
</body>
</html>