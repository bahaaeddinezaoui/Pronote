<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Teacher') {
    header('Location: login.php');
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
    <title><?php echo t('onboarding_select_categories') ?? 'Teaching assignments'; ?> - <?php echo t('app_name'); ?></title>
    <style>
        .page-surface {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
        }
        .ta-grid {
            display: grid;
            gap: 12px;
        }
        .ta-surface {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            background: var(--background-color);
        }
        .ta-muted {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .ta-major-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 12px;
            background: var(--surface-color);
        }
        .ta-major-title {
            font-weight: 700;
            color: var(--text-primary);
        }
        .ta-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div style="padding: 24px; max-width: 980px;">
            <div style="display:flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 16px;">
                <h1 style="margin: 0; font-size: 28px; font-weight: 800; color: var(--text-primary);">
                    <?php echo t('onboarding_select_categories') ?? 'Teaching assignments'; ?>
                </h1>
                <a class="btn btn-secondary" href="options.php" style="width:auto; text-decoration:none;">
                    <?php echo t('back') ?? 'Back'; ?>
                </a>
            </div>

            <div class="page-surface">
                <div class="ta-grid">
                    <div class="ta-muted">
                        <?php echo t('onboarding_step2_desc') ?? 'Select the categories, majors, and sections you teach.'; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ta_category_select"><?php echo t('onboarding_categories_label') ?? 'Category'; ?></label>
                        <select class="form-input" id="ta_category_select"></select>
                    </div>

                    <div id="ta_majors_container" class="ta-surface" style="display:none;">
                        <div style="font-weight: 700; margin-bottom: 10px;"><?php echo t('onboarding_select_majors') ?? 'Select majors'; ?></div>
                        <div id="ta_majors_list"></div>
                    </div>

                    <div id="ta_sections_container" class="ta-surface" style="display:none;">
                        <div style="font-weight: 700; margin-bottom: 10px;"><?php echo t('onboarding_select_sections_for_major') ?? 'Select sections for each major'; ?></div>
                        <div id="ta_sections_list"></div>
                    </div>

                    <div id="ta_selection_summary" class="ta-surface" style="display:none;">
                        <div style="font-weight: 700; margin-bottom: 6px;"><?php echo t('onboarding_selection_summary') ?? 'Summary'; ?></div>
                        <div id="ta_summary_text" class="ta-muted"></div>
                    </div>

                    <div class="ta-actions">
                        <button class="btn btn-primary" id="ta_save_btn" type="button" style="width:auto;">
                            <?php echo t('onboarding_save') ?? 'Save'; ?>
                        </button>
                        <div id="ta_save_msg" class="alert" style="display:none; margin: 0; flex: 1;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var T = <?php echo json_encode($T ?? []); ?>;

    var categorySelect = document.getElementById('ta_category_select');
    var majorsContainer = document.getElementById('ta_majors_container');
    var majorsList = document.getElementById('ta_majors_list');
    var sectionsContainer = document.getElementById('ta_sections_container');
    var sectionsList = document.getElementById('ta_sections_list');
    var selectionSummary = document.getElementById('ta_selection_summary');
    var summaryText = document.getElementById('ta_summary_text');
    var saveBtn = document.getElementById('ta_save_btn');
    var saveMsg = document.getElementById('ta_save_msg');

    var majorSectionsState = {}; // { [majorId]: { [sectionId]: true } }
    var majorNamesMap = {};

    function showAlert(text, type) {
        if (!saveMsg) return;
        saveMsg.textContent = text;
        saveMsg.style.display = 'block';
        saveMsg.className = 'alert ' + (type === 'success' ? 'alert-success' : 'alert-error');
    }

    function buildPayload() {
        var assignments = [];
        Object.keys(majorSectionsState).forEach(function(majorId) {
            var bySection = majorSectionsState[majorId] || {};
            var sectionIds = Object.keys(bySection).filter(function(sectionId) { return !!bySection[sectionId]; });
            if (sectionIds.length > 0) {
                assignments.push({ major_id: majorId, section_ids: sectionIds });
            }
        });
        return { assignments: assignments };
    }

    function updateSummary() {
        var payload = buildPayload();
        if (payload.assignments.length === 0) {
            if (selectionSummary) selectionSummary.style.display = 'none';
            return;
        }
        var majorCount = payload.assignments.length;
        var sectionCount = payload.assignments.reduce(function(total, a) { return total + a.section_ids.length; }, 0);
        if (summaryText) {
            summaryText.textContent = (T.onboarding_summary_text || 'You have selected %d major(s) across %d section(s).')
                .replace('%d', majorCount)
                .replace('%d', sectionCount);
        }
        if (selectionSummary) selectionSummary.style.display = 'block';
    }

    function loadExistingAssignments() {
        return fetch('get_teacher_teaching_assignments.php')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) return;
                (data.assignments || []).forEach(function(a) {
                    if (!a || !a.major_id || !a.section_id) return;
                    var majorId = String(a.major_id);
                    var sectionId = String(a.section_id);
                    if (!majorSectionsState[majorId]) majorSectionsState[majorId] = {};
                    majorSectionsState[majorId][sectionId] = true;
                });
                updateSummary();
            })
            .catch(function() {});
    }

    function loadCategories() {
        if (!categorySelect) return;
        categorySelect.innerHTML = '<option value="">' + (T.onboarding_loading_categories || 'Loading categories...') + '</option>';
        fetch('get_teacher_categories.php')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                categorySelect.innerHTML = '';
                if (!data || !data.success) {
                    var errOpt = document.createElement('option');
                    errOpt.value = '';
                    errOpt.textContent = T.onboarding_failed_load_categories || 'Failed to load categories';
                    categorySelect.appendChild(errOpt);
                    return;
                }
                (data.categories || []).forEach(function(c) {
                    var opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    categorySelect.appendChild(opt);
                });
                if (categorySelect.options.length > 0) {
                    categorySelect.value = categorySelect.options[0].value;
                    loadMajors();
                }
            })
            .catch(function() {
                categorySelect.innerHTML = '';
                var errOpt = document.createElement('option');
                errOpt.value = '';
                errOpt.textContent = T.onboarding_error_loading_categories || 'Error loading categories';
                categorySelect.appendChild(errOpt);
            });
    }

    function loadMajors() {
        var categoryId = categorySelect ? categorySelect.value : '';
        majorsContainer.style.display = categoryId ? 'block' : 'none';
        sectionsContainer.style.display = 'none';
        majorsList.innerHTML = '';
        sectionsList.innerHTML = '';
        if (!categoryId) return;

        majorsList.innerHTML = '<div style="text-align:center; padding: 18px;">' + (T.onboarding_loading_majors || 'Loading majors...') + '</div>';
        fetch('get_teacher_section_majors.php?section_id=' + encodeURIComponent(categoryId))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                majorsList.innerHTML = '';
                if (!data || !data.success) {
                    majorsList.innerHTML = '<div style="text-align:center; padding: 18px; color:#dc2626;">' + (T.onboarding_failed_load_majors || 'Failed to load majors') + '</div>';
                    return;
                }

                var majors = data.majors || [];
                if (majors.length === 0) {
                    majorsList.innerHTML = '<div style="text-align:center; padding: 18px; color: var(--text-secondary);">' + (T.onboarding_no_majors || 'No majors available') + '</div>';
                    return;
                }

                var controlsDiv = document.createElement('div');
                controlsDiv.style.marginBottom = '12px';
                controlsDiv.style.display = 'flex';
                controlsDiv.style.gap = '10px';
                controlsDiv.style.flexWrap = 'wrap';

                var selectAllBtn = document.createElement('button');
                selectAllBtn.type = 'button';
                selectAllBtn.className = 'btn btn-secondary';
                selectAllBtn.style.width = 'auto';
                selectAllBtn.textContent = T.onboarding_select_all_majors || 'Select all majors';
                selectAllBtn.addEventListener('click', function() {
                    majorsList.querySelectorAll('input[type="checkbox"][data-ta-major]').forEach(function(cb) {
                        if (!cb.checked) {
                            cb.checked = true;
                            onMajorChange(cb.value, true);
                        }
                    });
                });

                var clearAllBtn = document.createElement('button');
                clearAllBtn.type = 'button';
                clearAllBtn.className = 'btn btn-secondary';
                clearAllBtn.style.width = 'auto';
                clearAllBtn.textContent = T.onboarding_clear_all_majors || 'Clear all majors';
                clearAllBtn.addEventListener('click', function() {
                    majorsList.querySelectorAll('input[type="checkbox"][data-ta-major]').forEach(function(cb) {
                        if (cb.checked) {
                            cb.checked = false;
                            onMajorChange(cb.value, false);
                        }
                    });
                });

                controlsDiv.appendChild(selectAllBtn);
                controlsDiv.appendChild(clearAllBtn);
                majorsList.appendChild(controlsDiv);

                // Major search + render
                var searchWrap = document.createElement('div');
                searchWrap.style.display = 'flex';
                searchWrap.style.gap = '10px';
                searchWrap.style.alignItems = 'center';
                searchWrap.style.marginBottom = '12px';

                var searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'form-input';
                searchInput.placeholder = (T.search_major_placeholder || 'Search majors...');
                searchInput.style.maxWidth = '420px';

                searchWrap.appendChild(searchInput);
                majorsList.appendChild(searchWrap);

                var grid = document.createElement('div');
                grid.style.display = 'grid';
                grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(260px, 1fr))';
                grid.style.gap = '10px';
                majorsList.appendChild(grid);

                function renderMajors(filterText) {
                    var q = String(filterText || '').trim().toLowerCase();
                    grid.innerHTML = '';

                    if (!q) {
                        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align:center; padding: 16px; color: var(--text-secondary);">' + (T.search_major_prompt || 'Type to search majors.') + '</div>';
                        return;
                    }

                    var shown = 0;
                    majors.forEach(function(m) {
                        var name = String(m.name || '');
                        if (q && name.toLowerCase().indexOf(q) === -1) return;

                        shown++;
                        majorNamesMap[String(m.id)] = name;

                        var card = document.createElement('div');
                        card.className = 'ta-major-card';

                        var header = document.createElement('div');
                        header.style.display = 'flex';
                        header.style.alignItems = 'center';
                        header.style.gap = '10px';

                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.value = String(m.id);
                        cb.setAttribute('data-ta-major', '1');
                        cb.style.width = '16px';
                        cb.style.height = '16px';

                        if (majorSectionsState[String(m.id)] && Object.keys(majorSectionsState[String(m.id)]).some(function(sid){ return majorSectionsState[String(m.id)][sid]; })) {
                            cb.checked = true;
                        }

                        cb.addEventListener('change', function() {
                            onMajorChange(this.value, this.checked);
                        });

                        var title = document.createElement('div');
                        title.className = 'ta-major-title';
                        title.textContent = name;

                        header.appendChild(cb);
                        header.appendChild(title);
                        card.appendChild(header);
                        grid.appendChild(card);

                        if (cb.checked) {
                            loadSectionsForMajor(String(m.id));
                        }
                    });

                    if (shown === 0) {
                        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align:center; padding: 16px; color: var(--text-secondary);">' + (T.no_results || 'No results.') + '</div>';
                    }
                }

                searchInput.addEventListener('input', function() {
                    renderMajors(this.value);
                });

                renderMajors();
                updateSummary();
            })
            .catch(function() {
                majorsList.innerHTML = '<div style="text-align:center; padding: 18px; color:#dc2626;">' + (T.onboarding_error_loading_majors || 'Error loading majors') + '</div>';
            });
    }

    function onMajorChange(majorId, isChecked) {
        majorId = String(majorId);
        if (!majorSectionsState[majorId]) majorSectionsState[majorId] = {};
        if (isChecked) {
            loadSectionsForMajor(majorId);
        } else {
            majorSectionsState[majorId] = {};
            var div = document.getElementById('ta_sections_for_major_' + majorId);
            if (div) div.style.display = 'none';
        }
        updateSummary();
    }

    function loadSectionsForMajor(majorId) {
        var categoryId = categorySelect ? categorySelect.value : '';
        if (!categoryId) return;

        sectionsContainer.style.display = 'block';

        var existing = document.getElementById('ta_sections_for_major_' + majorId);
        if (existing) {
            existing.style.display = 'block';
            return;
        }

        var wrap = document.createElement('div');
        wrap.id = 'ta_sections_for_major_' + majorId;
        wrap.style.border = '1px solid var(--border-color)';
        wrap.style.borderRadius = '12px';
        wrap.style.padding = '14px';
        wrap.style.marginBottom = '12px';
        wrap.style.background = 'var(--surface-color)';

        var title = document.createElement('div');
        title.style.fontWeight = '800';
        title.style.marginBottom = '10px';
        title.textContent = (T.onboarding_sections_for || 'Sections for %s:')
            .replace('%s', majorNamesMap[majorId] || ('Major ' + majorId));
        wrap.appendChild(title);

        var loading = document.createElement('div');
        loading.textContent = T.onboarding_loading_sections || 'Loading sections...';
        wrap.appendChild(loading);
        sectionsList.appendChild(wrap);

        fetch('get_teacher_sections.php?category_id=' + encodeURIComponent(categoryId))
            .then(function(res){ return res.json(); })
            .then(function(data) {
                if (loading && loading.parentNode === wrap) wrap.removeChild(loading);
                if (!data || !data.success) {
                    var err = document.createElement('div');
                    err.style.color = '#dc2626';
                    err.textContent = T.onboarding_failed_load_sections || 'Failed to load sections';
                    wrap.appendChild(err);
                    return;
                }

                var sections = data.sections || [];
                if (sections.length === 0) {
                    var empty = document.createElement('div');
                    empty.style.color = '#6b7280';
                    empty.textContent = T.onboarding_no_sections || 'No sections available for this category';
                    wrap.appendChild(empty);
                    return;
                }

                var controls = document.createElement('div');
                controls.style.marginBottom = '8px';
                controls.style.display = 'flex';
                controls.style.gap = '8px';
                controls.style.flexWrap = 'wrap';

                function setAll(checked) {
                    wrap.querySelectorAll('input[type="checkbox"][data-ta-section]').forEach(function(cb) {
                        cb.checked = checked;
                        onSectionChange(majorId, cb.value, checked);
                    });
                }

                var selAll = document.createElement('button');
                selAll.type = 'button';
                selAll.className = 'btn btn-secondary';
                selAll.style.width = 'auto';
                selAll.textContent = T.onboarding_select_all || 'Select all';
                selAll.addEventListener('click', function(){ setAll(true); });

                var clrAll = document.createElement('button');
                clrAll.type = 'button';
                clrAll.className = 'btn btn-secondary';
                clrAll.style.width = 'auto';
                clrAll.textContent = T.onboarding_clear_all || 'Clear all';
                clrAll.addEventListener('click', function(){ setAll(false); });

                controls.appendChild(selAll);
                controls.appendChild(clrAll);
                wrap.appendChild(controls);

                var grid = document.createElement('div');
                grid.style.display = 'grid';
                grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(220px, 1fr))';
                grid.style.gap = '8px';

                sections.forEach(function(s) {
                    var row = document.createElement('label');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.gap = '8px';
                    row.style.cursor = 'pointer';

                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.value = String(s.id);
                    cb.setAttribute('data-ta-section', '1');

                    if (majorSectionsState[majorId] && majorSectionsState[majorId][String(s.id)]) {
                        cb.checked = true;
                    }

                    cb.addEventListener('change', function() {
                        onSectionChange(majorId, this.value, this.checked);
                    });

                    var span = document.createElement('span');
                    span.textContent = s.name;

                    row.appendChild(cb);
                    row.appendChild(span);
                    grid.appendChild(row);
                });

                wrap.appendChild(grid);
                updateSummary();
            })
            .catch(function() {
                if (loading && loading.parentNode === wrap) wrap.removeChild(loading);
                var err = document.createElement('div');
                err.style.color = '#dc2626';
                err.textContent = T.onboarding_error_loading_sections || 'Error loading sections';
                wrap.appendChild(err);
            });
    }

    function onSectionChange(majorId, sectionId, isChecked) {
        majorId = String(majorId);
        sectionId = String(sectionId);
        if (!majorSectionsState[majorId]) majorSectionsState[majorId] = {};
        majorSectionsState[majorId][sectionId] = !!isChecked;
        updateSummary();
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            loadMajors();
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            if (saveMsg) saveMsg.style.display = 'none';
            var payload = buildPayload();
            if (!payload.assignments || payload.assignments.length === 0) {
                showAlert(T.onboarding_select_at_least_one || 'Please select at least one major and section.', 'error');
                return;
            }

            saveBtn.disabled = true;
            fetch('teacher_update_teaching_assignments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.success) {
                    showAlert(T.onboarding_saved_success || 'Saved successfully.', 'success');
                    setTimeout(function() {
                        window.location.href = data.redirect || 'options.php';
                    }, 1200);
                } else {
                    showAlert((data && data.message) ? data.message : (T.onboarding_save_failed || 'Save failed.'), 'error');
                }
            })
            .catch(function() {
                showAlert(T.error_unexpected || 'An unexpected error occurred.', 'error');
            })
            .finally(function() {
                saveBtn.disabled = false;
            });
        });
    }

    loadExistingAssignments().finally(function() {
        loadCategories();
    });
})();
</script>
</body>
</html>

