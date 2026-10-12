</main>

<?php if ($currentUser): ?>
	</div>
	<?php require __DIR__ . '/user-controls.php'; ?>
	<script src="<?php echo app_url('assets/js/features/global/device-login-notifications.js'); ?>?v=20261006c"></script>
<?php endif; ?>

</body>
</html>
