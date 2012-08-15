<?php

class WWW_view_add extends WWW_Factory {

	// View Controller calls this function as output for page content
	public function render($input){
	
		// Loading translations
		$translations=$this->getTranslations();
		// Loading sitemap
		$sitemap=$this->getSitemap();
		// Loading view data
		$view=$this->getState('view');
		?>
			<div id="header">
				<a href="<?=$sitemap['page/contact']['url']?>"><?=$sitemap['page/contact']['meta-title']?></a>
				<a href="<?=$sitemap['page/about']['url']?>"><?=$sitemap['page/about']['meta-title']?></a>
				<a href="<?=$sitemap['add']['url']?>"><?=$sitemap['add']['meta-title']?></a>
				<a href="<?=$sitemap['list']['url']?>"><?=$sitemap['list']['meta-title']?></a>
				<a href="<?=$sitemap['home']['url']?>"><?=$sitemap['home']['meta-title']?></a>
			</div>
			<div id="body">
				<form method="post" action="www.api?www-command=movies-add">
					<input type="hidden" name="success-url" value="<?=$sitemap['list']['url']?>?ok-notification">
					<input type="hidden" name="failure-url" value="<?=$sitemap['add']['url']?>?fail-notification">
					<p><?=$translations['title']?>:</p>
					<p><input type="text" name="title" value=""/></p>
					<p><?=$translations['year']?>:</p>
					<p>
						<select name="year">
						<?php
						for($i=date('Y');$i>=1980;$i--){
							?>
							<option value="<?=$i?>"><?=$i?></option>
							<?php
						}
						?>
						</select>
					</p>
					<p><input type="submit" value="<?=$translations['add-movie']?>"/></p>
				</form>
				<?php
					// If this is set, then movie adding failed
					if(isset($input['fail-notification'])){
						echo '<p><b>'.$translations['problem-adding-movie'].'</b></p>';
					}
				?>
			</div>
		<?php
		
	}

}
	
?>