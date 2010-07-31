<?php if (!empty($pagination)): ?>
	<div class="pagination">
		Showing <?= $pagination['range'] ?> of <?= $pagination['count'] ?><br />
		<strong>Pages:</strong> <?= HtmlTable::pagination_links(array(
			'pagination' => $pagination
		)) ?>
	</div>
<?php endif; ?>
