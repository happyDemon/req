<?php defined('SYSPATH') or die('No direct script access.');

if(count($messages) > 0) {
	foreach($messages as $msg) { ?>

		<div class="alert alert-<?php
			echo $msg['type'];
			if(isset($msg['data']['block']) && $msg['data']['block'] != false)
				echo ' alert-block';
		?>">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<?php
				if(isset($msg['data']['title']))
					echo '<h4>'.$msg['data']['title'].'</h4>';?>
			<?=$msg['value'];?>
		</div>
	<?}
}