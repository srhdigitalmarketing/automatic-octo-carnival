    <div class="footer">
    <?php if(! empty( lang('Footer.notice') )) : ?>
    <div class="footer-notice">
    <?= lang('Footer.notice') ?>
    </div>
    <?php endif; ?>

    <div class="footer-menu">
    <a class="btn btn-outline-info" href="/p/dmca" role="button">DMCA</a>
    <a class="btn btn-outline-info" href="/p/privacy-policy" role="button">Privacy Policy</a>
    <a class="btn btn-outline-info" href="/p/terms-of-service" role="button">Terms of Service</a>
    </div>


<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("activee");
    var panel = this.nextElementSibling;
    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = panel.scrollHeight + "px";
    } 
  });
}
</script>