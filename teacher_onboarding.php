<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'Teacher') {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['needs_onboarding']) || !empty($_SESSION['last_login_at'])) {
    header('Location: teacher_home.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title><?php echo t('change_password'); ?> - <?php echo t('app_name'); ?></title>
    <style>
        /* ... existing styles ... */
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: var(--surface-color);
            padding: 24px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .modal-header {
            margin-bottom: 16px;
        }
        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .modal-body {
            margin-bottom: 24px;
            max-height: 400px;
            overflow-y: auto;
        }
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .summary-item {
            margin-bottom: 12px;
            padding: 12px;
            background: var(--background-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .summary-major {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        .summary-sections {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title"><?php echo t('confirm_selections_title') ?? 'Confirm Selections'; ?></div>
        </div>
        <div class="modal-body" id="modalSummaryBody">
            <!-- Content populated by JS -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="editBtn" style="width: auto;"><?php echo t('edit'); ?></button>
            <button class="btn btn-primary" id="confirmSubmitBtn" style="width: auto;"><?php echo t('submit'); ?></button>
        </div>
    </div>
</div>

<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div style="padding: 24px; max-width: 900px;">
            <div id="onboardingWelcome" style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; margin-bottom: 16px; <?php echo !empty($_SESSION['onboarding_welcome_seen']) ? 'display:none;' : ''; ?>">
                <h2 style="margin-top: 0; color: var(--text-primary); font-size: 20px; font-weight: 700;"><?php echo t('onboarding_welcome_title'); ?></h2>
                <p style="color: var(--text-muted); margin-top: 8px; line-height: 1.5;">
                    <?php echo t('onboarding_welcome_desc'); ?>
                </p>
                <div style="margin-top: 20px; display: grid; gap: 16px;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="background: var(--bg-info); color: #2563eb; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);"><?php echo t('onboarding_step1_title'); ?></div>
                            <div style="font-size: 14px; color: var(--text-secondary);"><?php echo t('onboarding_step1_desc'); ?></div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="background: var(--bg-info); color: #2563eb; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary);"><?php echo t('onboarding_step2_title'); ?></div>
                            <div style="font-size: 14px; color: var(--text-secondary);"><?php echo t('onboarding_step2_desc'); ?></div>
                        </div>
                    </div>
                </div>
                <button id="startOnboardingBtn" class="btn btn-primary" style="margin-top: 24px; width: auto; padding: 10px 24px;"><?php echo t('onboarding_get_started'); ?></button>
            </div>

            <div id="onboardingStep1Container" style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 16px; <?php echo (empty($_SESSION['onboarding_welcome_seen']) || !empty($_SESSION['onboarding_password_changed'])) ? 'display:none;' : ''; ?>">
                <div style="font-weight: 700; margin-bottom: 12px;">
                    <?php echo t('change_password'); ?>
                </div>

                <form id="onboardingChangePasswordForm">
                    <div class="form-group">
                        <label class="form-label" for="old_password"><?php echo t('old_password'); ?></label>
                        <div style="position: relative;">
                            <input class="form-input" type="password" id="old_password" required style="padding-right: 40px;">
                            <span class="toggle-password" data-target="old_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">
                                👁️
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password"><?php echo t('new_password'); ?></label>
                        <div style="position: relative;">
                            <input class="form-input" type="password" id="new_password" required style="padding-right: 40px;">
                            <span class="toggle-password" data-target="new_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">
                                👁️
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_new_password"><?php echo t('confirm_new_password'); ?></label>
                        <div style="position: relative;">
                            <input class="form-input" type="password" id="confirm_new_password" required style="padding-right: 40px;">
                            <span class="toggle-password" data-target="confirm_new_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">
                                👁️
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="onboardingChangePasswordBtn"><?php echo t('update_password'); ?></button>
                    <div id="onboardingChangePasswordMsg" class="alert mt-4" style="display:none; text-align: center;"></div>
                </form>
            </div>

            <div id="onboardingStep2Container" style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; <?php echo empty($_SESSION['onboarding_password_changed']) ? 'display:none;' : ''; ?>">
                <div style="font-weight: 700; margin-bottom: 12px;">
                    <?php echo t('onboarding_select_categories'); ?>
                </div>

                <div id="onboardingStep2">
                    <div class="form-group">
                        <label class="form-label" for="category_select"><?php echo t('onboarding_categories_label'); ?></label>
                        <select class="form-input" id="category_select"></select>
                    </div>

                    <div id="majors_container" style="display: none;">
                        <div style="font-weight: 600; margin-bottom: 12px;"><?php echo t('onboarding_select_majors'); ?></div>
                        <div id="majors_list"></div>
                    </div>

                    <div id="sections_container" style="display: none;">
                        <div style="font-weight: 600; margin-bottom: 12px;"><?php echo t('onboarding_select_sections_for_major'); ?></div>
                        <div id="sections_list"></div>
                    </div>
                    
                    <div id="selection_summary" style="margin-top: 16px; padding: 12px; background: var(--background-color); border-radius: 8px; display: none;">
                        <div style="font-weight: 600; margin-bottom: 4px;"><?php echo t('onboarding_selection_summary'); ?></div>
                        <div id="summary_text" style="font-size: 14px; color: var(--text-secondary);"></div>
                    </div>

                    <button class="btn btn-primary" id="saveOnboardingBtn" type="button"><?php echo t('onboarding_save'); ?></button>
                    <div id="saveOnboardingMsg" class="alert mt-4" style="display:none; text-align:center;"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function(){
    var T = <?php echo json_encode($T ?? []); ?>;

    var pwForm = document.getElementById('onboardingChangePasswordForm');
    var pwMsg = document.getElementById('onboardingChangePasswordMsg');
    var pwBtn = document.getElementById('onboardingChangePasswordBtn');

    var welcome = document.getElementById('onboardingWelcome');
    var startBtn = document.getElementById('startOnboardingBtn');
    var step1Container = document.getElementById('onboardingStep1Container');
    var step2Container = document.getElementById('onboardingStep2Container');
    var step2 = document.getElementById('onboardingStep2');

    var categorySelect = document.getElementById('category_select');
    var majorsContainer = document.getElementById('majors_container');
    var majorsList = document.getElementById('majors_list');
    var sectionsContainer = document.getElementById('sections_container');
    var sectionsList = document.getElementById('sections_list');

    var saveBtn = document.getElementById('saveOnboardingBtn');
    var saveMsg = document.getElementById('saveOnboardingMsg');
    var selectionSummary = document.getElementById('selection_summary');
    var summaryText = document.getElementById('summary_text');

    var confirmModal = document.getElementById('confirmModal');
    var modalSummaryBody = document.getElementById('modalSummaryBody');
    var confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    var editBtn = document.getElementById('editBtn');

    var majorSectionsState = {};
    var allCategoriesState = {}; // Persist selections across category changes
    var currentCategoryId = null;

    var majorNamesMap = {};
    var sectionNamesMap = {};

    function updateSummary() {
        var payload = buildPayload();
        if (payload.assignments && payload.assignments.length > 0) {
            var majorCount = payload.assignments.length;
            var sectionCount = payload.assignments.reduce(function(total, assignment) {
                return total + assignment.section_ids.length;
            }, 0);
            
            summaryText.textContent = (T.onboarding_summary_text || 'You have selected %d major(s) across %d section(s).').replace('%d', majorCount).replace('%d', sectionCount);
            selectionSummary.style.display = 'block';
        } else {
            selectionSummary.style.display = 'none';
        }
    }

    function showAlert(el, text, type) {
        el.textContent = text;
        el.style.display = 'block';
        el.className = 'alert mt-4 ' + (type === 'success' ? 'alert-success' : 'alert-error');
    }

    function unlockStep2() {
        if (step1Container) step1Container.style.display = 'none';
        step2Container.style.display = 'block';
        loadCategories();
    }

    if (startBtn) {
        startBtn.addEventListener('click', function() {
            if (welcome) welcome.style.display = 'none';
            if (step1Container) step1Container.style.display = 'block';
            fetch('teacher_onboarding_welcome_seen.php', { method: 'POST' });
        });
    }

    pwForm.addEventListener('submit', function(e){
        e.preventDefault();
        pwMsg.style.display = 'none';

        var oldPassword = document.getElementById('old_password').value;
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_new_password').value;

        if (!oldPassword || !newPassword || !confirmPassword) {
            showAlert(pwMsg, T.fields_required || 'All fields are required.', 'error');
            return;
        }
        if (newPassword !== confirmPassword) {
            showAlert(pwMsg, T.password_mismatch || 'Passwords do not match.', 'error');
            return;
        }

        pwBtn.disabled = true;

        fetch('teacher_onboarding_change_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ oldPassword: oldPassword, newPassword: newPassword, confirmPassword: confirmPassword })
        })
        .then(function(res){ return res.json(); })
        .then(function(data){
            if (data && data.success) {
                showAlert(pwMsg, (T.onboarding_password_updated || 'Password updated.'), 'success');
                unlockStep2();
            } else {
                showAlert(pwMsg, (data && data.message) ? data.message : (T.password_change_failed || 'Password change failed.'), 'error');
            }
        })
        .catch(function(){
            showAlert(pwMsg, T.error_unexpected || 'An unexpected error occurred.', 'error');
        })
        .finally(function(){
            pwBtn.disabled = false;
        });
    });

    // Password toggle logic
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🔒';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    });

    function loadCategories() {
        categorySelect.innerHTML = '<option value="">' + (T.onboarding_loading_categories || 'Loading categories...') + '</option>';
        fetch('get_teacher_categories.php')
            .then(function(res){ return res.json(); })
            .then(function(data){
                categorySelect.innerHTML = '';
                if (!data || !data.success) {
                    var errorOpt = document.createElement('option');
                    errorOpt.value = '';
                    errorOpt.textContent = T.onboarding_failed_load_categories || 'Failed to load categories';
                    categorySelect.appendChild(errorOpt);
                    return;
                }
                
                if (data.categories && data.categories.length > 0) {
                    (data.categories || []).forEach(function(c){
                        var opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        categorySelect.appendChild(opt);
                    });

                    if (categorySelect.options.length > 0) {
                        loadMajors(categorySelect.value);
                    }
                } else {
                    var emptyOpt = document.createElement('option');
                    emptyOpt.value = '';
                    emptyOpt.textContent = T.onboarding_no_categories || 'No categories available';
                    categorySelect.appendChild(emptyOpt);
                }
            })
            .catch(function(){
                categorySelect.innerHTML = '';
                var errorOpt = document.createElement('option');
                errorOpt.value = '';
                errorOpt.textContent = T.onboarding_error_loading_categories || 'Error loading categories';
                categorySelect.appendChild(errorOpt);
            });
    }

    categorySelect.addEventListener('change', function(){
        loadMajors(categorySelect.value);
    });

    function loadMajors(categoryId) {
        // Save current category's state before switching
        if (currentCategoryId && Object.keys(majorSectionsState).length > 0) {
            allCategoriesState[currentCategoryId] = JSON.parse(JSON.stringify(majorSectionsState));
        }
        
        currentCategoryId = categoryId;
        majorsContainer.style.display = 'none';
        sectionsContainer.style.display = 'none';
        majorsList.innerHTML = '';
        sectionsList.innerHTML = '';
        
        // Restore state for this category if it exists
        if (allCategoriesState[categoryId]) {
            majorSectionsState = JSON.parse(JSON.stringify(allCategoriesState[categoryId]));
        } else {
            majorSectionsState = {};
        }

        if (!categoryId) {
            return;
        }

        majorsContainer.style.display = 'block';
        majorsList.innerHTML = '<div style="text-align: center; padding: 20px;">' + (T.onboarding_loading_majors || 'Loading majors...') + '</div>';

        fetch('get_teacher_section_majors.php?section_id=' + encodeURIComponent(categoryId)) // Using existing endpoint but will return all majors
            .then(function(res){ return res.json(); })
            .then(function(data){
                majorsList.innerHTML = '';
                if (!data || !data.success) {
                    majorsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc2626;">' + (T.onboarding_failed_load_majors || 'Failed to load majors') + '</div>';
                    return;
                }

                if (data.majors && data.majors.length > 0) {
                    var controlsDiv = document.createElement('div');
                    controlsDiv.style.marginBottom = '16px';
                    controlsDiv.style.display = 'flex';
                    controlsDiv.style.gap = '12px';
                    controlsDiv.style.flexWrap = 'wrap';
                    
                    var selectAllBtn = document.createElement('button');
                    selectAllBtn.type = 'button';
                    selectAllBtn.textContent = T.onboarding_select_all_majors || 'Select All Majors';
                    selectAllBtn.className = 'btn btn-secondary';
                    selectAllBtn.style.cssText = `
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        color: white;
                        border: none;
                        padding: 10px 16px;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.25s ease;
                        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                    `;
                    selectAllBtn.innerHTML = '<span style="font-size: 16px;">✓</span> ' + (T.onboarding_select_all_majors || 'Select All Majors');
                    selectAllBtn.addEventListener('click', function(){
                        var checkboxes = majorsList.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(cb){
                            cb.checked = true;
                            onMajorChange(cb);
                        });
                    });
                    
                    var clearAllBtn = document.createElement('button');
                    clearAllBtn.type = 'button';
                    clearAllBtn.textContent = T.onboarding_clear_all_majors || 'Clear All Majors';
                    clearAllBtn.className = 'btn btn-secondary';
                    clearAllBtn.style.cssText = `
                        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                        color: white;
                        border: none;
                        padding: 10px 16px;
                        border-radius: 8px;
                        font-size: 14px;
                        font-weight: 500;
                        cursor: pointer;
                        transition: all 0.25s ease;
                        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                    `;
                    clearAllBtn.innerHTML = '<span style="font-size: 16px;">✕</span> ' + (T.onboarding_clear_all_majors || 'Clear All Majors');
                    clearAllBtn.addEventListener('click', function(){
                        var checkboxes = majorsList.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(cb){
                            cb.checked = false;
                            onMajorChange(cb);
                        });
                    });

                    // Add hover effects
                    selectAllBtn.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 4px 12px rgba(16, 185, 129, 0.3)';
                    });
                    selectAllBtn.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '0 2px 4px rgba(16, 185, 129, 0.2)';
                    });
                    
                    clearAllBtn.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.3)';
                    });
                    clearAllBtn.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '0 2px 4px rgba(239, 68, 68, 0.2)';
                    });
                    
                    controlsDiv.appendChild(selectAllBtn);
                    controlsDiv.appendChild(clearAllBtn);
                    majorsList.appendChild(controlsDiv);

                    // Major search
                    var searchWrap = document.createElement('div');
                    searchWrap.style.marginBottom = '12px';
                    searchWrap.style.display = 'flex';
                    searchWrap.style.gap = '10px';
                    searchWrap.style.alignItems = 'center';

                    var searchInput = document.createElement('input');
                    searchInput.type = 'text';
                    searchInput.className = 'form-input';
                    searchInput.placeholder = (T.search_major_placeholder || 'Search majors...');
                    searchInput.style.maxWidth = '420px';

                    searchWrap.appendChild(searchInput);
                    majorsList.appendChild(searchWrap);

                    var majorsGrid = document.createElement('div');
                    majorsGrid.style.display = 'grid';
                    majorsGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(250px, 1fr))';
                    majorsGrid.style.gap = '12px';

                    function renderMajors(filterText) {
                        var q = String(filterText || '').trim().toLowerCase();
                        majorsGrid.innerHTML = '';

                        if (!q) {
                            majorsGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align:center; padding: 16px; color: var(--text-secondary);">' + (T.search_major_prompt || 'Type to search majors.') + '</div>';
                            return;
                        }

                        var shown = 0;

                        (data.majors || []).forEach(function(m){
                            var name = String(m.name || '');
                            if (q && name.toLowerCase().indexOf(q) === -1) return;
                            shown++;

                        var majorDiv = document.createElement('div');
                        majorDiv.style.border = '1px solid var(--border-color)';
                        majorDiv.style.borderRadius = '8px';
                        majorDiv.style.padding = '12px';

                        var headerDiv = document.createElement('div');
                        headerDiv.style.display = 'flex';
                        headerDiv.style.alignItems = 'center';
                        headerDiv.style.gap = '8px';

                        var checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.id = 'major_' + m.id;
                        checkbox.value = m.id;
                        checkbox.style.width = '16px';
                        checkbox.style.height = '16px';
                        // Restore checked state if this major was previously selected
                        if (majorSectionsState[m.id]) {
                            checkbox.checked = true;
                        }
                        checkbox.addEventListener('change', function(){
                            onMajorChange(this);
                        });

                        var label = document.createElement('label');
                        label.htmlFor = 'major_' + m.id;
                        label.style.fontWeight = '600';
                        label.style.cursor = 'pointer';
                        label.style.flex = '1';
                        label.textContent = name;

                        majorNamesMap[m.id] = name;

                        headerDiv.appendChild(checkbox);
                        headerDiv.appendChild(label);
                        majorDiv.appendChild(headerDiv);

                        majorsGrid.appendChild(majorDiv);
                        
                        // If this major was previously selected, load its sections
                        if (majorSectionsState[m.id]) {
                            loadSectionsForMajor(m.id);
                        }
                        });

                        if (shown === 0) {
                            majorsGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align:center; padding: 16px; color: var(--text-secondary);">' + (T.no_results || 'No results.') + '</div>';
                        }
                    }

                    majorsList.appendChild(majorsGrid);

                    searchInput.addEventListener('input', function() {
                        renderMajors(this.value);
                    });

                    renderMajors();
                } else {
                    majorsList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary);">' + (T.onboarding_no_majors || 'No majors available') + '</div>';
                }
            })
            .catch(function(){
                majorsList.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc2626;">' + (T.onboarding_error_loading_majors || 'Error loading majors') + '</div>';
            });
    }

    function onMajorChange(checkbox) {
        var majorId = checkbox.value;
        var isChecked = checkbox.checked;
        
        if (!majorSectionsState[majorId]) {
            majorSectionsState[majorId] = {};
        }
        
        if (isChecked) {
            // Show sections for this major
            loadSectionsForMajor(majorId);
        } else {
            // Hide sections and clear selections for this major
            var sectionDiv = document.getElementById('sections_for_major_' + majorId);
            if (sectionDiv) {
                sectionDiv.style.display = 'none';
            }
            // Clear all section selections for this major
            Object.keys(majorSectionsState[majorId]).forEach(function(sectionId) {
                majorSectionsState[majorId][sectionId] = false;
            });
        }
        updateSummary();
    }

    function loadSectionsForMajor(majorId) {
        sectionsContainer.style.display = 'block';
        
        // Check if sections for this major are already loaded
        var existingDiv = document.getElementById('sections_for_major_' + majorId);
        if (existingDiv) {
            existingDiv.style.display = 'block';
            return;
        }

        var categoryId = categorySelect.value;
        
        var majorSectionDiv = document.createElement('div');
        majorSectionDiv.id = 'sections_for_major_' + majorId;
        majorSectionDiv.style.border = '1px solid var(--border-color)';
        majorSectionDiv.style.borderRadius = '12px';
        majorSectionDiv.style.padding = '16px';
        majorSectionDiv.style.marginBottom = '16px';
        majorSectionDiv.style.background = 'var(--surface-color)';

        var majorLabelEl = document.querySelector('label[for="major_' + majorId + '"]');
        var majorName = majorLabelEl ? majorLabelEl.textContent : ((T.onboarding_major_fallback || 'Major') + ' ' + majorId);
        
        var title = document.createElement('div');
        title.style.fontWeight = '700';
        title.style.marginBottom = '12px';
        title.textContent = (T.onboarding_sections_for || 'Sections for %s:').replace('%s', majorName);
        majorSectionDiv.appendChild(title);

        var loadingDiv = document.createElement('div');
        loadingDiv.textContent = T.onboarding_loading_sections || 'Loading sections...';
        majorSectionDiv.appendChild(loadingDiv);

        sectionsList.appendChild(majorSectionDiv);

        fetch('get_teacher_sections.php?category_id=' + encodeURIComponent(categoryId))
            .then(function(res){ return res.json(); })
            .then(function(data){
                if (loadingDiv && loadingDiv.parentNode === majorSectionDiv) {
                    majorSectionDiv.removeChild(loadingDiv);
                }
                if (!data || !data.success) {
                    var errorDiv = document.createElement('div');
                    errorDiv.textContent = T.onboarding_failed_load_sections || 'Failed to load sections';
                    errorDiv.style.color = '#dc2626';
                    majorSectionDiv.appendChild(errorDiv);
                    return;
                }

                if (data.sections && data.sections.length > 0) {
                    var controlsDiv = document.createElement('div');
                    controlsDiv.style.marginBottom = '8px';
                    
                    var selectAllBtn = document.createElement('button');
                    selectAllBtn.type = 'button';
                    selectAllBtn.textContent = T.onboarding_select_all || 'Select All';
                    selectAllBtn.style.marginRight = '8px';
                    selectAllBtn.style.padding = '4px 8px';
                    selectAllBtn.style.border = '1px solid var(--border-color)';
                    selectAllBtn.style.borderRadius = '4px';
                    selectAllBtn.style.background = 'var(--surface-color)';
                    selectAllBtn.style.color = 'var(--text-primary)';
                    selectAllBtn.style.cursor = 'pointer';
                    selectAllBtn.style.fontSize = '12px';
                    selectAllBtn.addEventListener('click', function(){
                        var checkboxes = majorSectionDiv.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(cb){
                            cb.checked = true;
                            onSectionChange(majorId, cb.value, true);
                        });
                    });
                    
                    var clearAllBtn = document.createElement('button');
                    clearAllBtn.type = 'button';
                    clearAllBtn.textContent = T.onboarding_clear_all || 'Clear All';
                    clearAllBtn.style.padding = '4px 8px';
                    clearAllBtn.style.border = '1px solid var(--border-color)';
                    clearAllBtn.style.borderRadius = '4px';
                    clearAllBtn.style.background = 'var(--surface-color)';
                    clearAllBtn.style.color = 'var(--text-primary)';
                    clearAllBtn.style.cursor = 'pointer';
                    clearAllBtn.style.fontSize = '12px';
                    clearAllBtn.addEventListener('click', function(){
                        var checkboxes = majorSectionDiv.querySelectorAll('input[type="checkbox"]');
                        checkboxes.forEach(function(cb){
                            cb.checked = false;
                            onSectionChange(majorId, cb.value, false);
                        });
                    });
                    
                    controlsDiv.appendChild(selectAllBtn);
                    controlsDiv.appendChild(clearAllBtn);
                    majorSectionDiv.appendChild(controlsDiv);

                    var sectionsGrid = document.createElement('div');
                    sectionsGrid.style.display = 'grid';
                    sectionsGrid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(200px, 1fr))';
                    sectionsGrid.style.gap = '8px';

                    (data.sections || []).forEach(function(s){
                        var row = document.createElement('label');
                        row.style.display = 'flex';
                        row.style.alignItems = 'center';
                        row.style.gap = '6px';
                        row.style.cursor = 'pointer';

                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.value = s.id;
                        // Restore checked state if this section was previously selected
                        if (majorSectionsState[majorId] && majorSectionsState[majorId][s.id]) {
                            cb.checked = true;
                        }
                        cb.addEventListener('change', function(){
                            onSectionChange(majorId, this.value, this.checked);
                        });

                        var span = document.createElement('span');
                        span.textContent = s.name;

                        sectionNamesMap[s.id] = s.name;

                        row.appendChild(cb);
                        row.appendChild(span);
                        sectionsGrid.appendChild(row);
                    });

                    majorSectionDiv.appendChild(sectionsGrid);
                } else {
                    var emptyDiv = document.createElement('div');
                    emptyDiv.textContent = T.onboarding_no_sections || 'No sections available for this category';
                    emptyDiv.style.color = '#6b7280';
                    majorSectionDiv.appendChild(emptyDiv);
                }
            })
            .catch(function(){
                if (loadingDiv && loadingDiv.parentNode === majorSectionDiv) {
                    majorSectionDiv.removeChild(loadingDiv);
                }
                var errorDiv = document.createElement('div');
                errorDiv.textContent = T.onboarding_error_loading_sections || 'Error loading sections';
                errorDiv.style.color = '#dc2626';
                majorSectionDiv.appendChild(errorDiv);
            });
    }

    function onSectionChange(majorId, sectionId, isChecked) {
        if (!majorSectionsState[majorId]) {
            majorSectionsState[majorId] = {};
        }
        majorSectionsState[majorId][sectionId] = isChecked;
        updateSummary();
    }

    function buildPayload() {
        // Merge all categories state with current state
        var mergedState = {};
        
        // Copy all saved category states
        Object.keys(allCategoriesState).forEach(function(catId) {
            var catState = allCategoriesState[catId];
            Object.keys(catState).forEach(function(majorId) {
                if (!mergedState[majorId]) {
                    mergedState[majorId] = { section_ids: [], major_name: '' };
                }
                // We need to capture major names too for the summary
                // This is a bit tricky since major names are only available in the DOM when loaded
                // Let's ensure major names are captured in onMajorChange/onSectionChange
            });
        });
        
        // Merge current category state
        Object.keys(majorSectionsState).forEach(function(majorId) {
            if (!mergedState[majorId]) {
                mergedState[majorId] = { section_ids: [], major_name: '' };
            }
        });
        
        var assignments = [];
        Object.keys(mergedState).forEach(function(majorId){
            var sections = mergedState[majorId] || {};
            var sectionIds = Object.keys(sections).filter(function(sectionId){ return sections[sectionId]; });
            if (sectionIds.length > 0) {
                assignments.push({ major_id: majorId, section_ids: sectionIds });
            }
        });
        return { assignments: assignments };
    }

    function showConfirmModal() {
        var payload = buildPayload();
        modalSummaryBody.innerHTML = '';
        
        // Collect all selections with their names for display
        var summaryData = [];
        
        // Process all categories and majors
        var allStates = [allCategoriesState, { current: majorSectionsState }];
        
        var selections = {}; // { majorId: { name: '', sections: [ { id: '', name: '' } ] } }

        // Helper to find names from DOM or state
        function getMajorName(id) {
            var el = document.querySelector('label[for="major_' + id + '"]');
            return el ? el.textContent : (majorNamesMap[id] || 'Major ' + id);
        }
        function getSectionName(majorId, sectionId) {
            var majorDiv = document.getElementById('sections_for_major_' + majorId);
            if (majorDiv) {
                var cb = majorDiv.querySelector('input[value="' + sectionId + '"]');
                if (cb && cb.nextElementSibling) return cb.nextElementSibling.textContent;
            }
            return sectionNamesMap[sectionId] || 'Section ' + sectionId;
        }

        // We'll update the state management to keep track of names
        // For now, let's build the visual summary from the built payload and maps
        
        var hasSelections = false;
        
        // Using a more robust way to gather the names
        // I will add maps to store names as they are loaded
        
        Object.keys(allCategoriesState).forEach(function(catId) {
            var catState = allCategoriesState[catId];
            Object.keys(catState).forEach(function(majorId) {
                var sectionIds = Object.keys(catState[majorId]).filter(function(sid) { return catState[majorId][sid]; });
                if (sectionIds.length > 0) {
                    hasSelections = true;
                    var majorName = majorNamesMap[majorId] || 'Major ' + majorId;
                    var sectionNames = sectionIds.map(function(sid) { return sectionNamesMap[sid] || 'Section ' + sid; });
                    
                    var item = document.createElement('div');
                    item.className = 'summary-item';
                    item.innerHTML = '<div class="summary-major">' + majorName + '</div>' +
                                   '<div class="summary-sections">' + sectionNames.join(', ') + '</div>';
                    modalSummaryBody.appendChild(item);
                }
            });
        });
        
        // current category
        Object.keys(majorSectionsState).forEach(function(majorId) {
            var sectionIds = Object.keys(majorSectionsState[majorId]).filter(function(sid) { return majorSectionsState[majorId][sid]; });
            if (sectionIds.length > 0) {
                hasSelections = true;
                var majorName = majorNamesMap[majorId] || 'Major ' + majorId;
                var sectionNames = sectionIds.map(function(sid) { return sectionNamesMap[sid] || 'Section ' + sid; });
                
                var item = document.createElement('div');
                item.className = 'summary-item';
                item.innerHTML = '<div class="summary-major">' + majorName + '</div>' +
                               '<div class="summary-sections">' + sectionNames.join(', ') + '</div>';
                modalSummaryBody.appendChild(item);
            }
        });

        if (hasSelections) {
            confirmModal.style.display = 'flex';
        } else {
            showAlert(saveMsg, T.onboarding_select_at_least_one || 'Please select at least one major and section.', 'error');
        }
    }

    saveBtn.addEventListener('click', function(){
        showConfirmModal();
    });

    editBtn.addEventListener('click', function() {
        confirmModal.style.display = 'none';
    });

    confirmSubmitBtn.addEventListener('click', function() {
        confirmModal.style.display = 'none';
        performSave();
    });

    function performSave() {
        saveMsg.style.display = 'none';
        var payload = buildPayload();

        saveBtn.disabled = true;
        fetch('teacher_onboarding_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(res){ return res.json(); })
        .then(function(data){
            if (data && data.success) {
                showAlert(saveMsg, T.onboarding_saved_success || 'Saved successfully.', 'success');
                setTimeout(function(){
                    window.location.href = data.redirect || 'teacher_home.php';
                }, 800);
            } else {
                showAlert(saveMsg, (data && data.message) ? data.message : (T.onboarding_save_failed || 'Save failed.'), 'error');
            }
        })
        .catch(function(){
            showAlert(saveMsg, T.error_unexpected || 'An unexpected error occurred.', 'error');
        })
        .finally(function(){
            saveBtn.disabled = false;
        });
    }

    <?php if (!empty($_SESSION['onboarding_password_changed'])): ?>
    unlockStep2();
    <?php endif; ?>
})();
</script>

</body>
</html>
