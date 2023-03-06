        </main>
    </div>
</div>
<script type="text/javascript" src="/js/jquery.inputmask.min.js"></script>
<script type="text/javascript" src="/js/bootstrap.min.js"></script>
<script type="text/javascript" src="/js/moment.min.js"></script>
<script type="text/javascript" src="/js/daterangepicker.min.js"></script>
<script type="text/javascript" src="/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="/js/dataTables.bootstrap4.min.js"></script>
<script type="text/javascript" src="/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="/js/buttons.bootstrap4.min.js"></script>
<script type="text/javascript" src="/js/buttons.colVis.min.js"></script>
<script type="text/javascript" src="/js/toastify.min.js"></script>
<script type="text/javascript" src="/js/modals.js"></script>
<script type="text/javascript" src="/js/bootstrap-select.min.js"></script>
<script type="text/javascript">
    $(function() {
<?php if (isset($_SESSION['FLASH_ERROR'])): ?>
        showError('<?php echo $_SESSION['FLASH_ERROR']; unset($_SESSION['FLASH_ERROR']); ?>');
<?php endif; ?>
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('[autofocus]').trigger('focus');
        });
    });
</script>
</body>
</html>