<?php
/**
 * Shared layout for Log in and Create account: one centered, clean card.
 *
 * Usage:
 *   auth_stage_start('login');   // or 'register' (wider card)
 *   ... page content ...
 *   auth_stage_end();
 */
function auth_stage_start($mode) {
    $wide = $mode === 'register' ? ' signin-card-wide' : '';
    ?>
<main class="signin-page is-skeleton" id="main" tabindex="-1">
    <!-- Skeleton card shown for a moment after the page opens, then replaced by the real form -->
    <div class="sk-authcard<?php echo $wide; ?>" aria-hidden="true">
        <span class="sk sk-logo"></span>
        <span class="sk sk-bar" style="width:55%"></span>
        <span class="sk sk-bar sk-bar-sm" style="width:80%"></span>
        <span class="sk sk-field"></span>
        <span class="sk sk-field"></span>
        <?php if ($mode === 'register'): ?><span class="sk sk-field"></span><span class="sk sk-field"></span><?php endif; ?>
        <span class="sk sk-btn"></span>
    </div>
    <div class="signin-card<?php echo $wide; ?>">
        <div class="signin-brand">
            <img class="signin-logo" src="<?php echo asset_v('/assets/images/logo.png'); ?>" alt="CSO-NORSU logo" width="48" height="48"
                 onerror="this.style.display='none'">
            <div>
                <p class="signin-name">CSO-NORSU</p>
                <p class="signin-org">Computer Science Organization</p>
            </div>
        </div>
    <?php
}

function auth_stage_end() {
    ?>
    </div>
    <button type="button" class="theme-toggle signin-theme" id="themeToggle" aria-label="Switch between light and dark mode" title="Switch theme">
        <svg class="icon i-sun" aria-hidden="true"><use href="#i-sun"/></svg>
        <svg class="icon i-moon" aria-hidden="true"><use href="#i-moon"/></svg>
    </button>
    <script>
    (function () {
        var m = document.currentScript.parentNode, t0 = Date.now(), gone = false;
        function reveal() { if (gone) return; gone = true; m.classList.remove('is-skeleton'); }
        function done() { setTimeout(reveal, Math.max(0, 450 - (Date.now() - t0))); }
        if (document.readyState === 'complete') done(); else window.addEventListener('load', done);
        setTimeout(reveal, 4000);   /* never keep the form hidden */
        window.addEventListener('pageshow', function (e) { if (e.persisted) reveal(); });
    })();
    </script>
</main>
    <?php
}
