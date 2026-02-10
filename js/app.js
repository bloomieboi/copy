/**
 * КопиПейст - Главный JS файл (ES6+)
 * Alpine.js компоненты и утилиты
 */

// Компонент для управления заказами
document.addEventListener('alpine:init', () => {
    // Компонент калькулятора цены
    Alpine.data('priceCalculator', () => ({
        quantity: 1,
        basePrice: 0,
        useBonuses: 0,
        maxBonuses: 0,
        
        init() {
            this.basePrice = parseFloat(this.$el.dataset.basePrice) || 0;
            this.maxBonuses = parseInt(this.$el.dataset.maxBonuses) || 0;
        },
        
        get totalPrice() {
            return Math.max(0, (this.basePrice * this.quantity) - this.useBonuses);
        },
        
        get formattedPrice() {
            return this.totalPrice.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' руб.';
        },
        
        incrementQuantity() {
            this.quantity++;
        },
        
        decrementQuantity() {
            if (this.quantity > 1) this.quantity--;
        }
    }));
    
    // Компонент уведомлений
    Alpine.data('notifications', () => ({
        show: false,
        message: '',
        type: 'success', // success, error, info, warning
        
        notify(message, type = 'success') {
            this.message = message;
            this.type = type;
            this.show = true;
            
            setTimeout(() => {
                this.show = false;
            }, 5000);
        },
        
        close() {
            this.show = false;
        }
    }));
    
    // Компонент модального окна
    Alpine.data('modal', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
            if (this.open) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
        
        close() {
            this.open = false;
            document.body.style.overflow = '';
        }
    }));
    
    // Компонент поиска/фильтрации
    Alpine.data('searchFilter', () => ({
        search: '',
        items: [],
        
        init() {
            this.items = Array.from(this.$el.querySelectorAll('[data-searchable]'));
        },
        
        get filteredItems() {
            if (!this.search) return this.items;
            
            const searchLower = this.search.toLowerCase();
            return this.items.filter(item => {
                const text = item.textContent.toLowerCase();
                const match = text.includes(searchLower);
                item.style.display = match ? '' : 'none';
                return match;
            });
        }
    }));
});

// Утилиты
const Utils = {
    // Форматирование цены
    formatPrice(price) {
        return parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' руб.';
    },
    
    // Копирование в буфер обмена
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            console.error('Failed to copy:', err);
            return false;
        }
    },
    
    // Debounce функция
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Валидация телефона
    validatePhone(phone) {
        return /^\d{11}$/.test(phone.replace(/\D/g, ''));
    },
    
    // Форматирование телефона
    formatPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length === 11) {
            return `+${cleaned[0]} (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9)}`;
        }
        return phone;
    }
};

// Экспорт для использования
window.Utils = Utils;

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', () => {
    console.log('КопиПейст загружен успешно');
    
    // Автоматическая прокрутка к якорям
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Автофокус на первое поле формы
    const firstInput = document.querySelector('form input:not([type="hidden"]):not([disabled])');
    if (firstInput) {
        firstInput.focus();
    }
});
