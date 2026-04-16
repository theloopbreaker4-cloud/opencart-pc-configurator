/**
 * PC Configurator — GCOMP.GE
 */
var Configurator = (function() {
    'use strict';

    var selected = {};
    var allComponents = [];
    var currentCategoryId = null;
    var STORAGE_KEY = 'cfg_selected';

    function saveSelected() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(selected)); } catch(e) {}
    }

    function loadSelected() {
        try {
            var data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            if (data && typeof data === 'object') {
                selected = data;
                for (var id in selected) {
                    updateSlot(id);
                }
                updateTotal();
                checkCompatibility();
            }
        } catch(e) {}
    }

    // qty-enabled categories — limits loaded from CfgConfig.settings
    var qtyCategories = {};

    function initQtyCategories() {
        var s = (CfgConfig.settings || {});
        qtyCategories = {
            3:  s.qtyRam     || 4,
            8:  s.qtySsd     || 4,
            9:  s.qtyHdd     || 4,
            10: s.qtyCasefan || 8,
            11: s.qtyMonitor || 3
        };
    }

    function getQtyMax(categoryId) {
        if (categoryId == 3) {
            var mbName = selected[2] ? selected[2].name : '';
            if (/Mini[\s-]?ITX/i.test(mbName)) return 2;
            return qtyCategories[3] || 4;
        }
        return qtyCategories[categoryId] || 1;
    }

    // ==================== CUSTOM MODAL ====================

    function showModal(type, message, onConfirm) {
        var iconHtml = '';
        var btnClass = 'btn-cfg-modal-ok';
        if (type === 'success') {
            iconHtml = '<div class="cfg-modal-icon cfg-modal-success"><i class="fa fa-check-circle"></i></div>';
            btnClass = 'btn-cfg-modal-success';
        } else if (type === 'error') {
            iconHtml = '<div class="cfg-modal-icon cfg-modal-error"><i class="fa fa-exclamation-circle"></i></div>';
            btnClass = 'btn-cfg-modal-error';
        } else if (type === 'warning') {
            iconHtml = '<div class="cfg-modal-icon cfg-modal-warning"><i class="fa fa-exclamation-triangle"></i></div>';
            btnClass = 'btn-cfg-modal-warning';
        } else if (type === 'confirm') {
            iconHtml = '<div class="cfg-modal-icon cfg-modal-confirm"><i class="fa fa-question-circle"></i></div>';
        }

        var confirmBtn = '';
        if (type === 'confirm') {
            confirmBtn = '<button class="btn btn-cfg-modal-cancel" id="cfgModalCancel">' + CfgConfig.texts.cancel + '</button>' +
                '<button class="btn btn-cfg-modal-ok" id="cfgModalOk">' + CfgConfig.texts.confirm + '</button>';
        } else {
            confirmBtn = '<button class="btn ' + btnClass + '" id="cfgModalOk">OK</button>';
        }

        var html = '<div id="cfgCustomModal" style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999">' +
            '<div id="cfgModalOverlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);opacity:0;transition:opacity 0.3s"></div>' +
            '<div style="position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;pointer-events:none">' +
            '<div id="cfgModalBox" style="pointer-events:auto;background:#fff;border-radius:16px;padding:35px 30px 25px;max-width:420px;width:90%;text-align:center;box-shadow:0 25px 70px rgba(0,0,0,0.3);transform:scale(0.8);opacity:0;transition:transform 0.2s,opacity 0.2s">' +
            iconHtml +
            '<div style="font-size:15px;color:#333;line-height:1.6;margin-bottom:25px">' + message + '</div>' +
            '<div style="display:flex;gap:10px;justify-content:center">' + confirmBtn + '</div>' +
            '</div></div></div>';

        $('body').append(html);

        setTimeout(function() {
            $('#cfgModalOverlay').css('opacity', '1');
            $('#cfgModalBox').css({opacity: 1, transform: 'scale(1)'});
        }, 10);

        $('#cfgModalOk').on('click', function() {
            closeCustomModal();
            if (onConfirm) onConfirm();
        });
        $('#cfgModalCancel, #cfgModalOverlay').on('click', function() {
            closeCustomModal();
        });
    }

    function closeCustomModal() {
        $('#cfgModalOverlay').css('opacity', '0');
        $('#cfgModalBox').css({opacity: 0, transform: 'scale(0.8)'});
        setTimeout(function() { $('#cfgCustomModal').remove(); }, 300);
    }

    function showAlert(message) { showModal('warning', message); }
    function showSuccess(message, callback) { showModal('success', message, callback); }
    function showError(message) { showModal('error', message); }
    function showConfirm(message, onConfirm) { showModal('confirm', message, onConfirm); }

    // ==================== COMPONENT SELECTION ====================

    function openSelector(categoryId, categoryName) {
        currentCategoryId = categoryId;
        $('#cfg-modal-title').text(categoryName);
        $('#cfg-search-input').val('');
        $('#cfg-component-list').html('<div class="text-center" style="padding:40px;color:#999"><i class="fa fa-spinner fa-spin"></i></div>');
        $('#cfg-modal').modal('show');

        $.getJSON(CfgConfig.apiGetComponents + '&category_id=' + categoryId, function(data) {
            allComponents = data.components || [];
            renderComponents(allComponents);
        });
    }

    function renderComponents(components) {
        var $list = $('#cfg-component-list');
        if (!components.length) {
            $list.html('<div class="text-center" style="padding:40px;color:#999">' + CfgConfig.texts.noComponents + '</div>');
            return;
        }

        var selectedId = selected[currentCategoryId] ? selected[currentCategoryId].id : null;
        var html = '';

        $.each(components, function(i, comp) {
            var cls = comp.component_id == selectedId ? ' active' : '';
            var specs = '';
            if (comp.specs && typeof comp.specs === 'object') {
                var parts = [];
                for (var k in comp.specs) parts.push(comp.specs[k]);
                specs = parts.join(' | ');
            }

            html += '<div class="cfg-comp-item' + cls + '" data-id="' + comp.component_id + '" data-name="' + escAttr(comp.name) + '" data-price="' + comp.price + '" data-price-fmt="' + escAttr(comp.price_formatted) + '">';
            if (comp.image) html += '<img class="cfg-comp-img" src="' + comp.image + '">';
            html += '<div class="cfg-comp-info"><div class="cfg-comp-name">' + esc(comp.name) + '</div>';
            if (specs) html += '<div class="cfg-comp-specs">' + esc(specs) + '</div>';
            html += '</div><div class="cfg-comp-price">';
            if (comp.original_price && comp.original_price > 0) {
                html += '<span style="text-decoration:line-through;color:#999;font-size:12px;display:block">' + comp.original_price_formatted + '</span>';
            }
            html += comp.price_formatted + '</div></div>';
        });

        $list.html(html);

        $list.off('click', '.cfg-comp-item').on('click', '.cfg-comp-item', function() {
            var $el = $(this);
            var hasQty = currentCategoryId in qtyCategories;

            if (hasQty) {
                showQtyCard($el);
            } else {
                selected[currentCategoryId] = {
                    id: $el.data('id'),
                    name: $el.data('name'),
                    price: parseFloat($el.data('price')),
                    price_formatted: $el.data('price-fmt')
                };
                saveSelected();
                updateSlot(currentCategoryId);
                updateTotal();
                checkCompatibility();
                $('#cfg-modal').modal('hide');
            }
        });
    }

    function showQtyCard($el) {
        var currentQty = (selected[currentCategoryId] && selected[currentCategoryId].id == $el.data('id'))
            ? (selected[currentCategoryId].quantity || 1) : 1;
        var imgSrc = $el.find('.cfg-comp-img').attr('src') || '';
        var imgHtml = imgSrc ? '<img src="' + imgSrc + '" style="width:80px;height:80px;object-fit:contain;margin-bottom:12px">' : '';

        var html = '<div class="cfg-qty-card">' +
            '<button class="cfg-qty-back" id="cfg-qty-back"><i class="fa fa-arrow-left"></i></button>' +
            imgHtml +
            '<div class="cfg-qty-card-name">' + esc($el.data('name')) + '</div>' +
            '<div class="cfg-qty-card-price">' + esc($el.data('price-fmt')) + '</div>' +
            '<div class="cfg-qty-controls">' +
            '<button class="btn btn-default" id="cfg-qty-minus">-</button>' +
            '<span id="cfg-qty-val">' + currentQty + '</span>' +
            '<button class="btn btn-default" id="cfg-qty-plus">+</button>' +
            '</div>' +
            '<button class="btn btn-danger cfg-qty-confirm-btn" id="cfg-qty-confirm">' + CfgConfig.texts.add + '</button>' +
            '</div>';

        $('#cfg-component-list').html(html);
        $('#cfg-modal-footer').hide();

        var qtyMax = getQtyMax(currentCategoryId);

        $('#cfg-qty-minus').on('click', function() {
            var q = parseInt($('#cfg-qty-val').text()) - 1;
            if (q < 1) q = 1;
            $('#cfg-qty-val').text(q);
        });
        $('#cfg-qty-plus').on('click', function() {
            var q = parseInt($('#cfg-qty-val').text()) + 1;
            if (q > qtyMax) q = qtyMax;
            $('#cfg-qty-val').text(q);
        });
        $('#cfg-qty-back').on('click', function() {
            renderComponents(allComponents);
        });
        $('#cfg-qty-confirm').on('click', function() {
            var qty = parseInt($('#cfg-qty-val').text());
            selected[currentCategoryId] = {
                id: $el.data('id'),
                name: $el.data('name'),
                price: parseFloat($el.data('price')),
                price_formatted: $el.data('price-fmt'),
                quantity: qty
            };
            saveSelected();
            updateSlot(currentCategoryId);
            updateTotal();
            checkCompatibility();
            $('#cfg-modal').modal('hide');
        });
    }

    function filterComponents(query) {
        query = query.toLowerCase().trim();
        if (!query) { renderComponents(allComponents); return; }
        renderComponents(allComponents.filter(function(c) {
            return c.name.toLowerCase().indexOf(query) !== -1;
        }));
    }

    function removeComponent(categoryId) {
        delete selected[categoryId];
        saveSelected();
        updateSlot(categoryId);
        updateTotal();
        checkCompatibility();
    }

    function clearAll() {
        if (Object.keys(selected).length === 0) return;
        showConfirm(CfgConfig.texts.confirmClear, function() {
            var ids = Object.keys(selected);
            selected = {};
            saveSelected();
            $.each(ids, function(i, id) { updateSlot(id); });
            $('.cfg-slot').each(function() { updateSlot($(this).data('category-id')); });
            updateTotal();
            $('#cfg-compat').hide();
        });
    }

    function updateSlot(categoryId) {
        var $slot = $('.cfg-slot[data-category-id="' + categoryId + '"]');
        var comp = selected[categoryId];

        if (comp) {
            $slot.addClass('selected');
            var qty = comp.quantity || 1;
            var label = comp.name + (qty > 1 ? ' <span class="cfg-slot-qty-badge">x' + qty + '</span>' : '');
            $slot.find('.cfg-selected-name').html(label);
            $slot.find('.cfg-slot-price').text(formatNumber(comp.price * qty) + ' ₾');
            $slot.find('.act-add').hide();
            $slot.find('.act-change').show();
            $slot.find('.btn-cfg-remove').show();
        } else {
            $slot.removeClass('selected');
            $slot.find('.cfg-selected-name').text('');
            $slot.find('.cfg-slot-price').text('');
            $slot.find('.act-add').show();
            $slot.find('.act-change').hide();
            $slot.find('.btn-cfg-remove').hide();
        }
    }

    function updateTotal() {
        var total = 0;
        for (var id in selected) total += selected[id].price * (selected[id].quantity || 1);
        $('#cfg-total-price').text(formatNumber(total));
    }

    function formatNumber(n) {
        return n.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function checkCompatibility() {
        var components = {};
        var count = 0;
        for (var id in selected) { components[id] = selected[id].id; count++; }
        if (count < 2) { $('#cfg-compat').hide(); return; }

        $.ajax({
            url: CfgConfig.apiCheckCompatibility,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ components: components }),
            success: function(data) {
                var $el = $('#cfg-compat');
                var errors = formatCompatMessages(data.error_codes || [], data.errors || []);
                var warnings = formatCompatMessages(data.warning_codes || [], data.warnings || []);
                if (errors.length) {
                    $el.attr('class', 'compat-error').html('<i class="fa fa-exclamation-triangle"></i> ' + errors.map(esc).join('<br>')).show();
                    $('.btn-cart').prop('disabled', true).css('opacity', '0.5');
                } else if (warnings.length) {
                    $el.attr('class', 'compat-warning').html('<i class="fa fa-warning"></i> ' + warnings.map(esc).join('<br>')).show();
                    $('.btn-cart').prop('disabled', false).css('opacity', '');
                } else {
                    $el.attr('class', 'compat-ok').html('<i class="fa fa-check"></i> ' + CfgConfig.texts.compatOk).show();
                    $('.btn-cart').prop('disabled', false).css('opacity', '');
                }
            }
        });
    }

    function downloadPdf() {
        if (!Object.keys(selected).length) { showAlert(CfgConfig.texts.selectFirst); return; }

        var data = {};
        for (var id in selected) {
            data[id] = { id: selected[id].id, qty: selected[id].quantity || 1 };
        }
        var url = CfgConfig.apiDownloadPdf + '&cfg=' + btoa(JSON.stringify(data));
        window.open(url, '_blank');
    }

    function openOrderForm() {
        if (!Object.keys(selected).length) { showAlert(CfgConfig.texts.selectFirst); return; }

        // Validate required categories
        var missing = [];
        var req = CfgConfig.requiredCategories || {};
        for (var catId in req) {
            if (!selected[catId]) {
                missing.push(req[catId]);
            }
        }
        if (missing.length) {
            showAlert(CfgConfig.texts.missingComponents + ':<br><br>' + missing.map(function(n) { return '• ' + esc(n); }).join('<br>'));
            return;
        }

        var html = '';
        var total = 0;
        for (var catId in selected) {
            var comp = selected[catId];
            var catName = $('.cfg-slot[data-category-id="' + catId + '"] .cfg-slot-name').text().replace(' *', '');
            html += '<div class="cfg-order-line"><span>' + esc(catName) + ': ' + esc(comp.name) + '</span><span>' + comp.price_formatted + '</span></div>';
            total += comp.price;
        }
        html += '<div class="cfg-order-total"><span>' + CfgConfig.texts.total + ':</span><span>' + formatNumber(total) + ' ₾</span></div>';

        $('#cfg-order-summary').html(html);
        $('#cfg-order-form')[0].reset();
        $('#cfg-order-modal').modal('show');
    }

    function submitOrder() {
        var $form = $('#cfg-order-form');
        var payload = {
            customer_name: $form.find('[name=customer_name]').val(),
            customer_phone: $form.find('[name=customer_phone]').val(),
            customer_email: $form.find('[name=customer_email]').val(),
            comment: $form.find('[name=comment]').val(),
            components: getSelectedIds()
        };

        if (!payload.customer_name || !payload.customer_phone) {
            showAlert(CfgConfig.texts.fillRequired);
            return;
        }

        var $btn = $('#cfg-order-modal .btn-danger');
        var origBtnText = $btn.text();
        $btn.prop('disabled', true).text(CfgConfig.texts.sending + '...');

        $.ajax({
            url: CfgConfig.apiSubmitOrder,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(data) {
                if (data.error) {
                    showError(data.error);
                } else {
                    $('#cfg-order-modal').modal('hide');
                    // Add to cart silently if not already added
                    var orderedIds = getSelectedIds();
                    var prevIds = [];
                    try { prevIds = JSON.parse(localStorage.getItem('cfg_cart_ids') || '[]'); } catch(e) {}
                    var currentProductIds = Object.keys(orderedIds).map(function(k) { return parseInt(orderedIds[k]); });
                    var alreadyAdded = currentProductIds.length > 0 && currentProductIds.every(function(pid) { return prevIds.indexOf(pid) !== -1; });
                    if (!alreadyAdded) {
                        addToCartSilent(orderedIds);
                    }
                    showSuccess(data.success || CfgConfig.texts.orderSuccess, function() {
                        selected = {};
                        saveSelected();
                        $('.cfg-slot').each(function() { updateSlot($(this).data('category-id')); });
                        updateTotal();
                        $('#cfg-compat').hide();
                    });
                }
            },
            error: function() { showError(CfgConfig.texts.error); },
            complete: function() {
                $btn.prop('disabled', false).text(origBtnText);
            }
        });
    }

    function addToCartSilent(components) {
        var addItems = {};
        for (var catId in components) {
            var qty = selected[catId] ? (selected[catId].quantity || 1) : 1;
            addItems[components[catId]] = qty;
        }
        cartReplaceAndUpdate(addItems, false);
    }

    function saveConfig() {
        var components = getSelectedIds();
        if (!Object.keys(components).length) { showAlert(CfgConfig.texts.selectFirst); return; }

        $.ajax({
            url: CfgConfig.apiSaveConfig,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ components: components }),
            success: function(data) {
                if (data.success) {
                    showSuccess(CfgConfig.texts.configSaved + '<br><br>' + CfgConfig.texts.configCode + ': <strong>' + data.code + '</strong><br><br>' + CfgConfig.texts.configUseLater);
                } else {
                    showError(data.error || CfgConfig.texts.error);
                }
            },
            error: function() { showError(CfgConfig.texts.error); }
        });
    }

    function openLoadForm() {
        $('#cfg-load-code').val('');
        $('#cfg-load-modal').modal('show');
    }

    function loadConfig() {
        var code = $('#cfg-load-code').val().trim();
        if (!code) return;

        $.ajax({
            url: CfgConfig.apiLoadConfig,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ code: code }),
            success: function(data) {
                if (data.error) {
                    showError(data.error);
                } else if (data.success && data.components) {
                    // Clear current selection
                    selected = {};
                    $('.cfg-slot').each(function() { updateSlot($(this).data('category-id')); });

                    // Load components
                    for (var catId in data.components) {
                        var comp = data.components[catId];
                        selected[catId] = {
                            id: comp.id,
                            name: comp.name,
                            price: comp.price,
                            price_formatted: comp.price_formatted
                        };
                        updateSlot(catId);
                    }
                    updateTotal();
                    checkCompatibility();
                    $('#cfg-load-modal').modal('hide');
                    showSuccess(CfgConfig.texts.configLoaded);
                }
            },
            error: function() { showError(CfgConfig.texts.error); }
        });
    }

    function requestDiscount() {
        if (!Object.keys(selected).length) { showAlert(CfgConfig.texts.selectFirst); return; }
        openOrderForm();
        var $comment = $('#cfg-order-form [name=comment]');
        if (!$comment.val()) $comment.val(CfgConfig.texts.discountComment);
    }

    function cartReplaceAndUpdate(addItems, showUI) {
        var prevIds = [];
        try { prevIds = JSON.parse(localStorage.getItem('cfg_cart_ids') || '[]'); } catch(e) {}

        var payload = { remove_ids: prevIds, add_items: addItems };

        $.ajax({
            url: CfgConfig.apiCartReplace,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function(data) {
                try { localStorage.setItem('cfg_cart_ids', JSON.stringify(data.added || [])); } catch(e) {}
                // Update Chameleon cart counter
                $('.shopping-cart .cart-content').load('index.php?route=common/cart/info .cart-content > *');
                if (showUI) {
                    closeCustomModal();
                    showConfirm((CfgConfig.texts.cartSuccess || 'Added to cart!'), function() {
                        window.location.href = CfgConfig.cartUrl;
                    });
                }
            },
            error: function() {
                if (showUI) {
                    closeCustomModal();
                    showError(CfgConfig.texts.error);
                }
            }
        });
    }

    function addToCart() {
        var components = getSelectedIds();
        if (!Object.keys(components).length) { showAlert(CfgConfig.texts.selectFirst); return; }

        showModal('warning', CfgConfig.texts.addingToCart || 'Adding to cart...');

        var addItems = {};
        for (var catId in components) {
            addItems[components[catId]] = selected[catId] ? (selected[catId].quantity || 1) : 1;
        }

        cartReplaceAndUpdate(addItems, true);
    }

    function getSelectedIds() {
        var result = {};
        for (var id in selected) result[id] = selected[id].id;
        return result;
    }

    function formatCompatMessages(codes, fallback) {
        if (codes && codes.length) {
            return codes.map(function(c) {
                var tpl = '';
                if (c.type === 'socket') tpl = CfgConfig.texts.errSocketMismatch;
                else if (c.type === 'ram') tpl = CfgConfig.texts.errRamMismatch;
                else if (c.type === 'form') tpl = CfgConfig.texts.errFormFactor;
                else if (c.type === 'psu') tpl = CfgConfig.texts.errPsuWarning;
                else if (c.type === 'rule') tpl = CfgConfig.texts.errRuleMismatch;
                else if (c.type === 'cooler_socket') tpl = CfgConfig.texts.errCoolerSocket;
                if (tpl) return tpl.replace('%s', c.p1).replace('%s', c.p2);
                return c.p1 + ' / ' + c.p2;
            });
        }
        return fallback || [];
    }

    function esc(s) { return $('<div>').text(s || '').html(); }
    function escAttr(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

    // Restore selected on page load
    $(function() { initQtyCategories(); loadSelected(); });

    return {
        openSelector: openSelector,
        filterComponents: filterComponents,
        removeComponent: removeComponent,
        clearAll: clearAll,
        downloadPdf: downloadPdf,
        openOrderForm: openOrderForm,
        submitOrder: submitOrder,
        requestDiscount: requestDiscount,
        saveConfig: saveConfig,
        openLoadForm: openLoadForm,
        loadConfig: loadConfig,
        addToCart: addToCart
    };
})();
