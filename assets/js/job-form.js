/**
 * Pure JS (no PHP) Job Form Logic.
 * Reads initial values via data-attributes on the <form> element.
 */
function jobForm() {
	return {
		isSubmitting: false,

		// Read initial state from dataset
		_initFromDataset() {
			const form = document.querySelector('form[x-data]');
			const ds = (form && form.dataset) ? form.dataset : {};
			this.csrf = ds.csrf || '';
			this.apiBase = ds.apiBase || '';
			this.customerId = ds.initialCustomerId ? parseInt(ds.initialCustomerId, 10) : null;
			this.customerQuery = ds.initialCustomerName || '';
			this.addressId = ds.initialAddressId ? parseInt(ds.initialAddressId, 10) : null;
			this.initialAddressId = this.addressId;
			this.serviceId = ds.initialServiceId ? parseInt(ds.initialServiceId, 10) : null;
			this.startAt = ds.initialStartAt || '';
			this.endAt = ds.initialEndAt || '';
			this.totalAmount = ds.initialTotalAmount || '';
			this.totalAmountTouched = !!this.totalAmount;
			this.alreadyPaid = ds.initialAlreadyPaid ? parseFloat(ds.initialAlreadyPaid) : 0;
			this.paymentAmount = ds.initialPaymentAmount || '';
			this.paymentDate = ds.initialPaymentDate || '';
			this.paymentNote = ds.initialPaymentNote || '';
		},

		// State
		csrf: '',
		apiBase: '',
		customerId: null,
		customerQuery: '',
		customerResults: [],
		showCustomerList: false,
		isInteractingWithCustomerList: false,
		nextCursor: null, // ROUND 12: Fix Alpine nextCursor error (pagination cursor for customer search)

		addresses: [],
		addressId: null,
		initialAddressId: null,
		showNewAddressForm: false,
		newAddress: { label: '', line: '', city: '' },

		showNewCustomerModal: false,
		newCustomer: { name: '', phone: '' },

		serviceId: null,
		serviceDuration: 0,
		startAt: '',
		endAt: '',

		totalAmount: '',
		totalAmountTouched: false,
		alreadyPaid: 0,
		paymentAmount: '',
		paymentDate: '',
		paymentNote: '',

		// Customer search
		async searchCustomers() {
			const q = this.customerQuery ? this.customerQuery.trim() : '';
			if (q.length < 2) {
				this.customerResults = [];
				this.showCustomerList = false;
				this.nextCursor = null; // Reset cursor on new search
				return;
			}
			const url = (this.apiBase || '') + '/api/search-customers?q=' + encodeURIComponent(q);
			const res = await fetch(url);
			const data = await res.json().catch(() => ({}));
			if (data && data.success) {
				this.customerResults = data.data || [];
				this.nextCursor = data.nextCursor || null; // ROUND 12: Set cursor from API response
				this.showCustomerList = true;
			} else {
				this.nextCursor = null;
			}
		},

		// Load more customers (pagination)
		async loadMoreCustomers() {
			if (!this.nextCursor) return;
			const q = this.customerQuery ? this.customerQuery.trim() : '';
			if (q.length < 2) return;
			try {
				const url = (this.apiBase || '') + '/api/search-customers?q=' + encodeURIComponent(q) + '&limit=20&cursor=' + encodeURIComponent(this.nextCursor);
				const res = await fetch(url);
				const data = await res.json().catch(() => ({}));
				if (data && data.success) {
					const more = data.data || [];
					this.customerResults = this.customerResults.concat(more).slice(0, 100); // Max 100 results
					this.nextCursor = data.nextCursor || null; // Update cursor
				} else {
					this.nextCursor = null;
				}
			} catch (e) {
				console.error('Load more customers error:', e);
				this.nextCursor = null;
			}
		},

		selectCustomer(item) {
			this.customerId = item.id;
			this.customerQuery = item.name;
			this.showCustomerList = false;
			this.loadAddresses();
		},

		onCustomerInputBlur() {
			const self = this;
			setTimeout(function(){
				if (!self.isInteractingWithCustomerList) {
					self.showCustomerList = false;
				}
			}, 150);
		},

		async loadAddresses() {
			if (!this.customerId) {
				this.addresses = [];
				this.addressId = null;
				return;
			}
			try {
				const url = (this.apiBase || '') + '/api/customers/' + this.customerId + '/addresses';
				const res = await fetch(url);
				const data = await res.json();
				if (data.success) {
					this.addresses = data.data || [];
					if (this.initialAddressId) this.addressId = this.initialAddressId;
				}
			} catch (e) {
				console.error('Error loading addresses:', e);
			}
		},

		async saveNewAddress() {
			if (!this.newAddress.line || !this.newAddress.line.trim() || !this.customerId) return;
			try {
				const url = (this.apiBase || '') + '/api/customers/' + this.customerId + '/addresses';
				const res = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-Token': this.csrf
					},
					body: JSON.stringify({
						label: this.newAddress.label,
						line: this.newAddress.line,
						city: this.newAddress.city
					})
				});
				const data = await res.json();
				if (data.success) {
					const newAddr = {
						id: data.data.address_id,
						label: this.newAddress.label,
						line: this.newAddress.line,
						city: this.newAddress.city
					};
					this.addresses.push(newAddr);
					this.addressId = newAddr.id;
					this.cancelNewAddress();
				} else {
					alert(data.error || 'Adres eklenemedi');
				}
			} catch (e) {
				alert('Bir hata oluştu');
			}
		},

		cancelNewAddress() {
			this.showNewAddressForm = false;
			this.newAddress = { label: '', line: '', city: '' };
		},

		openNewCustomerModal() {
			this.showNewCustomerModal = true;
			this.newCustomer = { name: (this.customerQuery || ''), phone: '' };
		},

		async saveNewCustomer() {
			if (!this.newCustomer.name || !this.newCustomer.name.trim()) {
				alert('Müşteri adı zorunludur.');
				return;
			}
			try {
				const url = (this.apiBase || '') + '/api/customers';
				const res = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-Token': this.csrf
					},
					body: JSON.stringify(this.newCustomer)
				});
				const data = await res.json();
				if (data && data.success && data.data && data.data.id) {
					this.customerId = data.data.id;
					this.customerQuery = this.newCustomer.name;
					this.showCustomerList = false;
					this.showNewCustomerModal = false;
					await this.loadAddresses();
				} else {
					alert((data && data.error) ? data.error : 'Müşteri oluşturulamadı');
				}
			} catch (e) {
				alert('Bir hata oluştu');
			}
		},

		cancelNewCustomer() {
			this.showNewCustomerModal = false;
			this.newCustomer = { name: '', phone: '' };
		},

		applyServiceDefaults() {
			const sel = document.querySelector('select[name=\"service_id\"]');
			const opt = sel ? sel.options[sel.selectedIndex] : null;
			if (!opt) return;
			const dur = parseInt(opt.getAttribute('data-duration') || '0', 10);
			const fee = parseFloat(opt.getAttribute('data-fee') || '0');
			this.serviceDuration = dur || 0;
			if (!this.totalAmountTouched && fee) {
				this.totalAmount = fee.toFixed(2);
			}
			if (this.startAt && dur) this.endAt = this.addMinutes(this.startAt, dur);
		},

		autoSetEnd() {
			if (this.startAt && this.serviceDuration) this.endAt = this.addMinutes(this.startAt, this.serviceDuration);
		},

		quickStart(mins) {
			const now = new Date();
			now.setMinutes(now.getMinutes() + mins);
			this.startAt = this.toInputValue(now);
			this.autoSetEnd();
		},

		addMinutes(dtLocal, mins) {
			const d = new Date(dtLocal);
			d.setMinutes(d.getMinutes() + mins);
			return this.toInputValue(d);
		},

		toInputValue(d) {
			const pad = (n) => String(n).padStart(2, '0');
			const y = d.getFullYear();
			const m = pad(d.getMonth()+1);
			const day = pad(d.getDate());
			const h = pad(d.getHours());
			const mi = pad(d.getMinutes());
			return `${y}-${m}-${day}T${h}:${mi}`;
		},

		parseNumber(value) {
			const num = parseFloat((value || '').toString().replace(',', '.'));
			return isNaN(num) ? 0 : num;
		},

		remainingAmount() {
			const total = this.parseNumber(this.totalAmount);
			const paid = this.parseNumber(this.alreadyPaid);
			return Math.max(total - paid, 0);
		},

		remainingAfterNew() {
			const total = this.parseNumber(this.totalAmount);
			const paid = this.parseNumber(this.alreadyPaid);
			const upcoming = this.parseNumber(this.paymentAmount);
			return Math.max(total - (paid + upcoming), 0);
		},

		formatMoney(value) {
			return this.parseNumber(value).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
		},

		init() {
			this._initFromDataset();
			if (this.customerId) this.loadAddresses();
			if (this.serviceId) this.applyServiceDefaults();
		}
	}
}

// Validation (pure JS)
function validateForm(form) {
	const customerId = form.querySelector('input[name=\"customer_id\"]').value;
	const startAt = form.querySelector('input[name=\"start_at\"]').value;
	const endAt = form.querySelector('input[name=\"end_at\"]').value;
	const totalAmount = form.querySelector('input[name=\"total_amount\"]').value;
	if (!customerId) { alert('Lütfen bir müşteri seçin.'); return false; }
	if (!startAt) { alert('Lütfen başlangıç tarihini girin.'); return false; }
	if (!endAt) { alert('Lütfen bitiş tarihini girin.'); return false; }
	if (!totalAmount || parseFloat(totalAmount) <= 0) { alert('Lütfen geçerli bir toplam tutar girin.'); return false; }
	if (new Date(endAt) <= new Date(startAt)) { alert('Bitiş tarihi başlangıç tarihinden sonra olmalıdır.'); return false; }
	return true;
}
