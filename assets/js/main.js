/* ================================================================
   Shoele Store — JavaScript principal
   ================================================================ */

// ─── Header Search ───────────────────────────────────────────────
// Em páginas que não o catálogo, redireciona para /?q=termo no Enter

(function () {
    const inp = document.getElementById('header-search');
    if (!inp || document.getElementById('products-grid')) return;
    inp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location.href = '/?q=' + encodeURIComponent(this.value.trim());
        }
    });
})();

// ─── Toast Notifications ─────────────────────────────────────────

function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => toast.remove(), 3500);
}

// ─── Carrinho ────────────────────────────────────────────────────

async function cartAdd(variantId, qty = 1) {
    try {
        const res = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', variant_id: variantId, qty })
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message, 'success');
            updateCartBadge(data.cart_count);
        } else {
            showToast(data.message, 'error');
        }
        return data;
    } catch (e) {
        showToast('Erro ao adicionar ao carrinho.', 'error');
    }
}

async function cartUpdate(variantId, qty) {
    const res = await fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'update', variant_id: variantId, qty })
    });
    const data = await res.json();

    if (data.success) {
        updateCartBadge(data.cart_count);
    } else {
        showToast(data.message, 'error');
    }
    return data;
}

async function cartRemove(variantId) {
    const res = await fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove', variant_id: variantId })
    });
    return await res.json();
}

function updateCartBadge(count) {
    let badge = document.querySelector('.cart-badge');
    const btn  = document.querySelector('.cart-btn');
    if (!btn) return;

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            btn.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// ─── Página de Produto ───────────────────────────────────────────

(function initProductPage() {
    const colorOptions  = document.querySelectorAll('.color-option');
    const sizeSelector  = document.querySelector('.size-selector');
    const addToCartBtn  = document.getElementById('add-to-cart-btn');
    const stockInfo     = document.getElementById('stock-info');

    if (!colorOptions.length) return;

    let selectedColor   = document.querySelector('.color-option.selected')?.dataset.color ?? null;
    let selectedVariant = null;

    // Event delegation no contentor — funciona com botões criados dinamicamente
    if (sizeSelector) {
        sizeSelector.addEventListener('click', e => {
            const btn = e.target.closest('.size-option');
            if (!btn || btn.classList.contains('out-of-stock')) return;

            sizeSelector.querySelectorAll('.size-option').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');

            selectedVariant = parseInt(btn.dataset.variantId);
            const stock     = parseInt(btn.dataset.stock);

            if (stockInfo) {
                if (stock === 0) {
                    stockInfo.textContent = 'Sem stock disponível';
                    stockInfo.className   = 'stock-info out';
                } else if (stock <= 3) {
                    stockInfo.textContent = `Últimas ${stock} unidades!`;
                    stockInfo.className   = 'stock-info low';
                } else {
                    stockInfo.textContent = `${stock} unidades disponíveis`;
                    stockInfo.className   = 'stock-info ok';
                }
            }

            if (addToCartBtn) addToCartBtn.disabled = (stock === 0);
        });
    }

    colorOptions.forEach(btn => {
        btn.addEventListener('click', async () => {
            colorOptions.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedColor   = btn.dataset.color;
            selectedVariant = null;

            const productId = btn.dataset.productId;
            const res   = await fetch(`/api/variant_stock.php?product_id=${productId}&color=${encodeURIComponent(selectedColor)}`);
            const sizes = await res.json();

            // Reconstruir os botões de tamanho para esta cor
            if (sizeSelector) {
                sizeSelector.innerHTML = '';
                sizes.forEach(v => {
                    const sizeBtn = document.createElement('button');
                    sizeBtn.type      = 'button';
                    sizeBtn.className = 'size-option' + (+v.stock === 0 ? ' out-of-stock' : '');
                    sizeBtn.dataset.size      = v.size;
                    sizeBtn.dataset.variantId = v.variant_id;
                    sizeBtn.dataset.stock     = v.stock;
                    sizeBtn.textContent = v.size;
                    sizeSelector.appendChild(sizeBtn);
                });
            }

            if (stockInfo)    stockInfo.textContent = '';
            if (addToCartBtn) addToCartBtn.disabled = true;
        });
    });

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', async () => {
            if (!selectedColor) {
                showToast('Seleciona uma cor.', 'error');
                return;
            }
            if (!selectedVariant) {
                showToast('Seleciona um tamanho.', 'error');
                return;
            }

            addToCartBtn.disabled    = true;
            addToCartBtn.textContent = 'A adicionar...';
            const result = await cartAdd(selectedVariant, 1);
            addToCartBtn.textContent = 'Adicionar ao Carrinho';
            if (result && result.success) addToCartBtn.disabled = false;
        });
    }
})();

// ─── Carrinho — Atualizar Quantidade ─────────────────────────────

document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const variantId = parseInt(btn.dataset.variantId);
        const delta     = parseInt(btn.dataset.delta);
        const valueEl   = btn.closest('.qty-control').querySelector('.qty-value');
        const currentQty = parseInt(valueEl.textContent);
        const newQty     = currentQty + delta;

        const result = await cartUpdate(variantId, newQty);
        if (result && result.success) {
            if (newQty <= 0) {
                // Remover linha da tabela
                btn.closest('tr').remove();
            } else {
                valueEl.textContent = newQty;
            }
            // Atualizar subtotais e total
            refreshCartTotals();
        }
    });
});

function refreshCartTotals() {
    // Recarrega a página para recalcular totais no servidor
    // Numa aplicação mais avançada, isto seria feito via AJAX
    location.reload();
}

// ─── POS ─────────────────────────────────────────────────────────

(function initPOS() {
    const searchInput = document.getElementById('pos-search');
    if (!searchInput) return;

    let posCart = [];

    // Pesquisa de produtos
    searchInput.addEventListener('input', debounce(filterProducts, 200));

    function filterProducts() {
        const q = searchInput.value.toLowerCase();
        document.querySelectorAll('.pos-product-card').forEach(card => {
            const text = card.dataset.search.toLowerCase();
            card.style.display = text.includes(q) ? '' : 'none';
        });
    }

    // Abrir modal de variantes ao clicar num produto
    document.querySelectorAll('.pos-product-card').forEach(card => {
        card.addEventListener('click', () => openVariantModal(card));
    });

    function openVariantModal(card) {
        const productId   = card.dataset.productId;
        const productName = card.dataset.name;

        fetch(`/api/variant_stock.php?product_id=${productId}&all=1`)
            .then(r => r.json())
            .then(variants => {
                const modal = document.getElementById('variant-modal');
                document.getElementById('modal-product-name').textContent = productName;

                const container = document.getElementById('modal-variants');
                container.innerHTML = '';

                // Agrupar por cor
                const byColor = {};
                variants.forEach(v => {
                    if (!byColor[v.color]) byColor[v.color] = [];
                    byColor[v.color].push(v);
                });

                Object.entries(byColor).forEach(([color, sizes]) => {
                    const colorGroup = document.createElement('div');
                    colorGroup.className = 'mb-3';
                    colorGroup.innerHTML = `<div class="selector-label">${color}</div>`;

                    const sizeRow = document.createElement('div');
                    sizeRow.className = 'size-selector';

                    sizes.forEach(v => {
                        const btn = document.createElement('button');
                        btn.className = 'size-option' + (v.stock === 0 ? ' out-of-stock' : '');
                        btn.textContent = v.size;
                        btn.title = `Stock: ${v.stock}`;
                        if (v.stock > 0) {
                            btn.addEventListener('click', () => {
                                addToPos(v, productName, card.dataset.price);
                                closeModal();
                            });
                        }
                        sizeRow.appendChild(btn);
                    });

                    colorGroup.appendChild(sizeRow);
                    container.appendChild(colorGroup);
                });

                modal.style.display = 'flex';
            });
    }

    window.closeModal = function() {
        document.getElementById('variant-modal').style.display = 'none';
    };

    document.getElementById('variant-modal')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });

    function addToPos(variant, name, basePrice) {
        const price = parseFloat(variant.price || basePrice);
        const existing = posCart.find(i => i.variant_id === variant.variant_id);

        if (existing) {
            if (existing.qty >= variant.stock) {
                showToast('Stock insuficiente.', 'error');
                return;
            }
            existing.qty++;
        } else {
            posCart.push({
                variant_id: variant.variant_id,
                name,
                color: variant.color,
                size:  variant.size,
                stock: variant.stock,
                price,
                qty: 1
            });
        }
        renderPosCart();
        showToast(`${name} (${variant.color} / ${variant.size}) adicionado.`, 'success');
    }

    function renderPosCart() {
        const container = document.getElementById('pos-cart-items');
        const emptyEl   = document.getElementById('pos-empty');
        const totalEl   = document.getElementById('pos-total');
        const finalizeBtn = document.getElementById('pos-finalize');

        container.innerHTML = '';

        if (posCart.length === 0) {
            emptyEl.style.display  = 'flex';
            finalizeBtn.disabled   = true;
            totalEl.textContent    = '0,00 €';
            return;
        }

        emptyEl.style.display = 'none';
        finalizeBtn.disabled  = false;

        let total = 0;

        posCart.forEach((item, idx) => {
            const subtotal = item.price * item.qty;
            total += subtotal;

            const el = document.createElement('div');
            el.className = 'pos-cart-item';
            el.innerHTML = `
                <div class="pos-cart-item-info">
                    <div class="pos-cart-item-name">${item.name}</div>
                    <div class="pos-cart-item-variant">${item.color} / Nº${item.size} × ${item.qty}</div>
                </div>
                <div class="pos-cart-item-price">${formatEur(subtotal)}</div>
                <button class="pos-remove-btn" data-idx="${idx}" title="Remover">×</button>
            `;
            container.appendChild(el);
        });

        totalEl.textContent = formatEur(total);

        container.querySelectorAll('.pos-remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                posCart.splice(parseInt(btn.dataset.idx), 1);
                renderPosCart();
            });
        });
    }

    document.getElementById('pos-finalize')?.addEventListener('click', async () => {
        if (posCart.length === 0) return;

        const btn = document.getElementById('pos-finalize');
        btn.disabled = true;
        btn.textContent = 'A processar...';

        try {
            const res = await fetch('/api/pos_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: posCart })
            });
            const data = await res.json();

            if (data.success) {
                showToast(`Venda #${data.sale_id} registada com sucesso!`, 'success');
                posCart = [];
                renderPosCart();
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            showToast('Erro ao registar venda.', 'error');
        }

        btn.disabled = false;
        btn.textContent = 'Finalizar Venda';
    });

    renderPosCart();
})();

// ─── Admin — Produto Form ─────────────────────────────────────────

(function initAdminProductForm() {
    const addVariantBtn = document.getElementById('add-variant-btn');
    if (!addVariantBtn) return;

    addVariantBtn.addEventListener('click', () => {
        const tbody = document.querySelector('#variants-tbody');
        const idx   = tbody.querySelectorAll('tr').length;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variants[${idx}][color]" placeholder="Cor" required class="form-control" style="width:120px"></td>
            <td><input type="text" name="variants[${idx}][size]"  placeholder="Ex: 42" required class="form-control" style="width:80px"></td>
            <td><input type="number" name="variants[${idx}][stock]" placeholder="0" min="0" value="0" class="form-control" style="width:80px"></td>
            <td><button type="button" class="btn btn-ghost btn-sm remove-variant-btn">✕</button></td>
        `;
        tr.querySelector('.remove-variant-btn').addEventListener('click', () => tr.remove());
        tbody.appendChild(tr);
    });

    document.querySelectorAll('.remove-variant-btn').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('tr').remove());
    });
})();

// ─── Utilities ───────────────────────────────────────────────────

function formatEur(val) {
    return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(val);
}

function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}
