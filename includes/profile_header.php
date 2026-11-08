<?php
/**
 * Facebook-style profile header: wide cover photo, avatar overlapping the
 * bottom-left with a status-note bubble, name + check mark, position and meta lines
 * and an action row on the right.
 *
 * $u    — users row (needs first/last name, role, profile_photo, cover_photo, status_note)
 * $opts — editable (bool: show upload / note controls), position (plain text, e.g. President),
 *         meta (html line under it, e.g. course and year), actions (html string)
 *
 * Call after includes/header.php (needs asset_v() and the icon sprite).
 */
function render_profile_header(array $u, array $opts = []) {
    $editable = !empty($opts['editable']);
    $position = trim((string) ($opts['position'] ?? ''));
    $meta     = $opts['meta'] ?? '';
    $actions  = $opts['actions'] ?? '';
    $cover    = $u['cover_photo'] ?? null;
    $photo    = $u['profile_photo'] ?? null;
    $note     = trim((string) ($u['status_note'] ?? ''));
    $verified = in_array($u['role'] ?? '', ['admin', 'officer', 'moderator'], true);
    ?>
    <section class="fb-profile">
        <div class="fb-cover<?php echo $cover ? '' : ' fb-cover-default'; ?>">
            <?php if ($cover): ?>
                <img src="<?php echo asset_v($cover); ?>" alt="" class="fb-cover-img" onerror="this.remove(); this.parentElement.classList.add('fb-cover-default')">
            <?php else: ?>
                <svg class="fb-cover-art" viewBox="0 0 800 220" preserveAspectRatio="xMidYMid slice" fill="none" aria-hidden="true">
                    <g stroke="currentColor" stroke-width="1.2" stroke-linecap="round">
                        <path d="M0 60h150l36 36h140"/><path d="M0 150h110l30-30h190"/><path d="M800 50H640l-38 38H480"/>
                        <path d="M800 160H690l-30 30H520"/><path d="M380 0v40l24 24"/><path d="M560 220v-40l-24-24"/>
                    </g>
                    <g fill="currentColor"><circle cx="326" cy="96" r="4"/><circle cx="330" cy="120" r="4"/><circle cx="480" cy="88" r="4"/><circle cx="520" cy="156" r="4"/></g>
                </svg>
            <?php endif; ?>

            <?php if ($editable): ?>
                <form method="POST" enctype="multipart/form-data" class="fb-upload-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="profile_action" value="cover">
                    <input type="file" name="cover_photo" id="coverInput" accept="image/png,image/jpeg,image/webp,image/gif" hidden onchange="this.form.submit()">
                    <label for="coverInput" class="fb-edit-cover"><svg class="icon" aria-hidden="true"><use href="#i-camera"/></svg>Edit cover photo</label>
                </form>
            <?php endif; ?>
        </div>

        <div class="fb-bar">
            <div class="fb-avatar-wrap">
                <?php if ($editable): ?>
                    <button type="button" class="fb-note<?php echo $note === '' ? ' fb-note-empty' : ''; ?>" data-note-toggle aria-expanded="false" aria-controls="noteForm">
                        <?php if ($note !== ''): ?>
                            <span class="fb-note-text"><?php echo e($note); ?></span>
                        <?php else: ?>
                            <span class="fb-note-text"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>Add note</span>
                        <?php endif; ?>
                    </button>
                    <form method="POST" class="fb-note-form" id="noteForm" hidden>
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="profile_action" value="note">
                        <input aria-label="Share a thought (60 characters)" type="text" name="status_note" maxlength="60" placeholder="Share a thought (60 characters)" value="<?php echo e($note); ?>">
                        <div class="fb-note-buttons">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <?php if ($note !== ''): ?><button type="submit" name="clear_note" value="1" class="btn btn-outline btn-sm">Clear</button><?php endif; ?>
                        </div>
                    </form>
                <?php elseif ($note !== ''): ?>
                    <div class="fb-note fb-note-static"><span class="fb-note-text"><?php echo e($note); ?></span></div>
                <?php endif; ?>

                <?php if ($photo): ?>
                    <img src="<?php echo asset_v($photo); ?>" alt="<?php echo e(full_name($u)); ?>" class="fb-avatar">
                <?php else: ?>
                    <span class="fb-avatar fb-avatar-fallback"><?php echo e(initials($u['first_name'] ?? '', $u['last_name'] ?? '')); ?></span>
                <?php endif; ?>

                <?php if ($editable): ?>
                    <form method="POST" enctype="multipart/form-data" class="fb-avatar-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="profile_action" value="avatar">
                        <input type="file" name="profile_photo" id="avatarInput" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                        <label for="avatarInput" class="fb-avatar-edit" title="Change profile photo"><svg class="icon" aria-hidden="true"><use href="#i-camera"/></svg><span class="sr-only">Change profile photo</span></label>
                    </form>

                    <div class="modal-overlay" id="avatarCropOverlay">
                        <div class="modal-box crop-box" role="dialog" aria-modal="true" aria-labelledby="avatarCropTitle">
                            <div class="modal-header">
                                <h3 id="avatarCropTitle">Adjust your photo</h3>
                                <button type="button" class="modal-close" aria-label="Close" data-crop-cancel>&times;</button>
                            </div>
                            <div class="modal-body">
                                <p class="crop-hint">Drag to move it. Use the slider to zoom.</p>
                                <div class="crop-stage-wrap">
                                    <canvas id="avatarCropStage" class="crop-stage" tabindex="0" aria-label="Photo position. Use the arrow keys to move it."></canvas>
                                    <span class="crop-ring" aria-hidden="true"></span>
                                </div>
                                <label for="avatarCropZoom" class="crop-zoom">
                                    <span>Zoom</span>
                                    <input type="range" id="avatarCropZoom" min="1" max="4" step="0.01" value="1">
                                </label>
                                <div class="crop-preview">
                                    <canvas id="avatarCropPreview" aria-hidden="true"></canvas>
                                    <span>How it looks in the grid</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline" data-crop-cancel>Cancel</button>
                                <button type="button" class="btn btn-primary" id="avatarCropSave">Save photo</button>
                            </div>
                        </div>
                    </div>
                    <script src="<?php echo asset_v('/assets/js/avatar_crop.js'); ?>" defer></script>
                <?php endif; ?>
            </div>

            <div class="fb-info">
                <?php
                    // Three plain-text lines: name + check, position (with "Developer"
                    // added for the portal's developer), then course and year.
                    // The check is glued to the last word so it never wraps alone.
                    $fn   = full_name($u);
                    $cut  = strrpos($fn, ' ');
                    $head = $cut === false ? '' : substr($fn, 0, $cut + 1);
                    $tail = $cut === false ? $fn : substr($fn, $cut + 1);
                    $role_parts = [];
                    if ($position !== '') {
                        // Each position group has its own colour and symbol: core officers a
                        // green star, committee heads a dark blue flag, committee members light
                        // blue people, plain members ("Member") a light green person.
                        $group_marks = [
                            'Core Officers'     => ['fb-core', 'CSO core officer', '<path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6-4.5-4.2 6.1-.7z"/>'],
                            'Committee Heads'   => ['fb-head', 'CSO committee head', '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>'],
                            'Committee Members' => ['fb-committee', 'CSO committee member', '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.2a3 3 0 0 1 0 5.6M18 14.4c1.8.9 3 2.8 3 5.6"/>'],
                            'Members'           => ['fb-member', 'CSO member', '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/>'],
                        ];
                        // Someone can hold several positions ("Secretary, Secretariat Head"):
                        // the first one sets the colour and gets the symbol, the others
                        // follow in that same colour without a symbol, separated by the dot.
                        $first_cls = null;
                        foreach (cso_position_list($position) as $i => $one) {
                            if ($i === 0) {
                                $group = $one === 'Member' ? 'Members' : cso_position_rank($one)[1];
                                if (isset($group_marks[$group])) {
                                    [$first_cls, $label, $paths] = $group_marks[$group];
                                    $role_parts[] = '<span class="fb-pos ' . $first_cls . '" title="' . $label . '">'
                                        . '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>'
                                        . e($one) . '</span>';
                                    continue;
                                }
                            }
                            $role_parts[] = $first_cls ? '<span class="fb-pos ' . $first_cls . '">' . e($one) . '</span>' : e($one);
                        }
                    }
                    if (($u['verified_as'] ?? '') === 'developer') {
                        $role_parts[] = '<span class="fb-dev" title="Developer of the CSO-NORSU portal">'
                            . '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/></svg>'
                            . 'Developer</span>';
                    }
                ?>
                <h1 class="fb-name"><?php echo e($head); ?><span class="fb-name-tail"><?php echo e($tail); ?><?php if ($verified): ?><svg class="icon icon-verified" role="img" aria-label="Verified CSO <?php echo e($u['role']); ?>"><title>Verified CSO <?php echo e($u['role']); ?></title><use href="#i-verified"/></svg><?php endif; ?></span></h1>
                <?php if ($role_parts): ?><p class="fb-role"><?php echo implode('<span class="fb-dot" aria-hidden="true">&#9679;</span>', $role_parts); ?></p><?php endif; ?>
                <?php if ($meta !== ''): ?><p class="fb-meta"><?php echo $meta; ?></p><?php endif; ?>
            </div>

            <?php if ($actions !== ''): ?><div class="fb-actions"><?php echo $actions; ?></div><?php endif; ?>
        </div>
    </section>
    <?php
}
