(() => {
  const themeButton = document.querySelector('[data-theme-toggle]');
  const setTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('riz-theme', theme); } catch (_) {}
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      const dark = theme === 'dark';
      button.innerHTML = `${dark ? '☀' : '☾'} <span>${dark ? 'Light mode' : 'Dark mode'}</span>`;
      button.setAttribute('aria-label', `Switch to ${dark ? 'light' : 'dark'} mode`);
      button.setAttribute('aria-pressed', String(dark));
    });
  };
  if (themeButton) {
    const savedTheme = document.documentElement.dataset.theme;
    setTheme(savedTheme === 'dark' ? 'dark' : 'light');
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => button.addEventListener('click', () => {
      setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    }));
  }

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const modal = document.getElementById(button.dataset.modalOpen || '');
      if (modal instanceof HTMLDialogElement && !modal.open) modal.showModal();
    });
  });
  document.querySelectorAll('.package-edit-modal').forEach((modal) => {
    modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', () => modal.close()));
    modal.addEventListener('click', (event) => {
      if (event.target === modal) modal.close();
    });
  });

  const dialog = document.getElementById('app-dialog');
  const dialogContent = document.getElementById('dialog-content');
  const dialogActions = dialog?.querySelector('.dialog-actions');
  let pendingForm = null;

  const openDialog = ({ title, text, href = '', confirm = false, onConfirm = null }) => {
    if (!dialog) return;
    dialogContent.replaceChildren();
    const heading = document.createElement('h2');
    heading.textContent = title;
    const message = document.createElement('p');
    message.textContent = text;
    dialogContent.append(heading, message);
    if (href) {
      const link = document.createElement('a');
      link.className = 'text-link';
      link.href = href;
      link.textContent = 'Open reservation details';
      dialogContent.append(link);
    }
    dialogActions.hidden = false;
    dialog.querySelector('#dialog-confirm').hidden = !confirm;
    dialog.querySelector('#dialog-cancel').textContent = confirm ? 'Cancel' : 'Close';
    pendingForm = onConfirm;
    dialog.showModal();
  };

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let approved = false;
    form.addEventListener('submit', (event) => {
      if (approved) return;
      event.preventDefault();
      const requestedStatus = form.querySelector('[name="status"]')?.value;
      const reasonType = requestedStatus === 'Cancelled' ? 'cancellation' : (requestedStatus === 'Rejected' ? 'rejection' : '');
      openDialog({
        title: 'Confirm action',
        text: form.dataset.confirm,
        confirm: true,
        onConfirm: () => {
          if (reasonType) {
            const reason = dialog.querySelector('#owner-action-reason')?.value.trim() || '';
            if (reason.length < 5 || reason.length > 1000) {
              const error = document.createElement('p');
              error.className = 'dialog-error';
              error.textContent = `Enter a ${reasonType} reason between 5 and 1,000 characters.`;
              dialogContent.append(error);
              return false;
            }
            const fieldName = reasonType === 'cancellation' ? 'cancellation_reason' : 'rejection_reason';
            let reasonInput = form.querySelector(`[name="${fieldName}"]`);
            if (!reasonInput) {
              reasonInput = document.createElement('input');
              reasonInput.type = 'hidden';
              reasonInput.name = fieldName;
              form.append(reasonInput);
            }
            reasonInput.value = reason;
          }
          approved = true;
          if (form.requestSubmit && event.submitter) form.requestSubmit(event.submitter);
          else if (form.requestSubmit) form.requestSubmit();
          else form.submit();
          return true;
        },
      });
      if (reasonType) {
        const label = document.createElement('label');
        label.className = 'field cancellation-field';
        label.htmlFor = 'owner-action-reason';
        label.textContent = reasonType === 'cancellation' ? 'Cancellation reason' : 'Rejection reason';
        const reason = document.createElement('textarea');
        reason.id = 'owner-action-reason';
        reason.maxLength = 1000;
        reason.required = true;
        reason.placeholder = reasonType === 'cancellation'
          ? 'Record why this reservation is being cancelled'
          : 'Record why this reservation is being rejected';
        dialogContent.append(label, reason);
      }
    });
  });

  document.querySelectorAll('[data-dialog-title]').forEach((button) => {
    button.addEventListener('click', () => openDialog({
      title: button.dataset.dialogTitle || 'Details',
      text: button.dataset.dialogText || '',
      href: button.dataset.dialogHref || '',
    }));
  });

  document.getElementById('dialog-cancel')?.addEventListener('click', () => dialog.close());
  document.getElementById('dialog-confirm')?.addEventListener('click', () => {
    const confirm = pendingForm;
    if (confirm && confirm() === false) return;
    pendingForm = null;
    dialog.close();
  });

  document.querySelectorAll('[data-payment]').forEach((button) => {
    button.addEventListener('click', () => {
      const paymentDialog = document.getElementById('payment-dialog');
      if (!paymentDialog) return;
      document.getElementById('payment-order-id').value = button.dataset.payment;
      const amount = document.getElementById('payment-amount');
      amount.max = button.dataset.balance;
      amount.value = '';
      paymentDialog.showModal();
    });
  });

  document.querySelectorAll('.flash-close').forEach((button) => button.addEventListener('click', () => button.closest('.flash').remove()));

  const packageSelect = document.getElementById('package');
  if (packageSelect) {
    const guestCount = document.getElementById('guest-count');
    const estimate = document.getElementById('price-estimate');
    const orderType = document.getElementById('order-type');
    const packagePicker = document.querySelector('[data-package-picker]');
    const packageLabel = packagePicker.querySelector('label[for="package"]');
    const packageEmpty = packagePicker.querySelector('[data-package-empty]');
    const alacarte = document.querySelector('[data-alacarte]');
    const bookingForm = document.querySelector('[data-order-form]');
    const updateMode = () => {
      const mode = orderType?.value || '';
      const packageMode = ['packed_meal', 'buffet', 'custom'].includes(mode);
      const trayMode = mode === 'alacarte';
      packageLabel.textContent = mode === 'packed_meal' ? 'Packed meal tier' : (mode === 'buffet' ? 'Buffet set' : 'Food package');
      packagePicker.hidden = !packageMode;
      packageSelect.required = packageMode;
      packageEmpty.hidden = !packageMode || [...packageSelect.options].some((option) => option.value && option.dataset.packageType === mode);
      alacarte.hidden = !trayMode;
      alacarte.querySelectorAll('input').forEach((input) => { input.disabled = !trayMode; });
      [...packageSelect.options].forEach((option) => {
        if (!option.value) return;
        option.hidden = !packageMode || option.dataset.packageType !== mode;
        if (option.hidden && option.selected) packageSelect.value = '';
      });
      if (!packageMode) packageSelect.value = '';
      updatePackage();
    };
    const updatePackage = () => {
      const option = packageSelect.selectedOptions[0];
      const mode = orderType?.value || '';
      const packageMode = ['packed_meal', 'buffet', 'custom'].includes(mode);
      const packageId = packageMode ? (option?.value || '') : '';
      const minimum = Number(option?.dataset.minimum || 1);
      const effectiveMinimum = ['packed_meal','buffet'].includes(mode) ? Math.max(50, minimum) : minimum;
      guestCount.min = effectiveMinimum;
      guestCount.dataset.minimum = effectiveMinimum;
      guestCount.max = option?.dataset.maximum || '';
      guestCount.dataset.maximum = option?.dataset.maximum || '';
      document.querySelectorAll('[data-package-options]').forEach((group) => {
        group.hidden = !packageId || group.dataset.packageOptions !== packageId;
        group.querySelectorAll('input').forEach((input) => { input.disabled = group.hidden; });
        if (!group.hidden) {
          const radios = group.querySelectorAll('input[type="radio"]');
          if (radios.length === 1) radios[0].checked = true;
        }
      });
      const updatePrice = () => {
        const trayGuests = Math.max(1, Number(guestCount.value || 1));
        const trayMinimum = Math.max(1, Math.ceil(trayGuests / 35));
        const trayMaximum = Math.max(1, Math.ceil(trayGuests / 25));
        document.querySelectorAll('[data-tray-guidance]').forEach((hint) => {
          const recommendation = trayMinimum === trayMaximum ? `${trayMinimum} tray${trayMinimum === 1 ? '' : 's'}` : `${trayMinimum}–${trayMaximum} trays`;
          hint.textContent = `For ${trayGuests} guests, consider ${recommendation} of this dish (about 25–35 people per tray).`;
        });
        if (mode === 'alacarte') {
          const lines = [...alacarte.querySelectorAll('input[data-item-price]')].reduce((sum, input) => sum + Math.max(0, Number(input.value || 0)) * Math.round(Number(input.dataset.itemPrice) * 100), 0);
          const money = (cents) => `₱${(cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
          estimate.textContent = lines ? `${money(lines)} estimated tray and dish total · ${money(Math.ceil(lines / 2))} required 50% deposit` : 'Choose one or more dishes and set a quantity for each tray.';
          return;
        }
        if (!packageMode) {
          estimate.textContent = 'Choose a food package, buffet, packed lunch, or food trays to continue.';
          return;
        }
        if (!option?.value) {
          estimate.textContent = 'Select a package to see the estimated total and 50% deposit.';
          return;
        }
        const price = Math.round(Number(option.dataset.price) * 100);
        const guests = Math.max(Number(guestCount.value || effectiveMinimum), effectiveMinimum);
        const packageTotal = option.dataset.pricing === 'Per Person' ? price * guests : price;
        const customizationCents = [...document.querySelectorAll('[data-package-options] input:checked:not(:disabled)')].reduce((sum, input) => sum + Math.round(Number(input.dataset.charge || 0) * 100) * (input.dataset.chargeType === 'per_person' ? guests : 1), 0);
        const total = packageTotal + customizationCents;
        const deposit = Math.ceil(total / 2);
        const money = (cents) => `₱${(cents / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        estimate.textContent = `${money(total)} estimated total · ${money(deposit)} required 50% deposit · ${guests} guests`;
      };
      updatePrice();
      guestCount.oninput = updatePrice;
      alacarte?.querySelectorAll('input[data-item-price]').forEach((input) => input.oninput = updatePrice);
    };
    packageSelect.addEventListener('change', updatePackage);
    document.querySelectorAll('[data-package-options] input').forEach((input) => input.addEventListener('change', () => {
      const group = input.closest('[data-package-options]');
      if (input.checked && input.type === 'checkbox') {
        const selected = group.querySelectorAll('input[type="checkbox"]:checked').length;
        const limit = Number(group.dataset.optionLimit || 0);
        if (limit && selected > limit) input.checked = false;
      }
      packageSelect.dispatchEvent(new Event('change'));
    }));
    orderType?.addEventListener('change', updateMode);
    updateMode();
    bookingForm?.addEventListener('submit', (event) => {
      if (Number(guestCount.value) < Number(guestCount.dataset.minimum || 1)) {
        event.preventDefault();
        guestCount.setCustomValidity(`This package requires at least ${guestCount.dataset.minimum} guests.`);
        guestCount.reportValidity();
      } else if (guestCount.dataset.maximum && Number(guestCount.value) > Number(guestCount.dataset.maximum)) {
        event.preventDefault();
        guestCount.setCustomValidity(`This package allows up to ${guestCount.dataset.maximum} guests.`);
        guestCount.reportValidity();
      } else {
        guestCount.setCustomValidity('');
      }
      if ((orderType?.value || '') === 'alacarte' && ![...alacarte.querySelectorAll('input[data-item-price]')].some((input) => Number(input.value) > 0)) {
        event.preventDefault();
        alacarte.querySelector('input[data-item-price]')?.setCustomValidity('Select at least one dish.');
        alacarte.querySelector('input[data-item-price]')?.reportValidity();
      } else alacarte?.querySelectorAll('input[data-item-price]').forEach((input) => input.setCustomValidity(''));
      if (['packed_meal','buffet'].includes(orderType?.value) && Number(guestCount.value) < 50) {
        event.preventDefault(); guestCount.setCustomValidity('Packed meals and buffet sets require at least 50 guests.'); guestCount.reportValidity();
      }
    });
  }
})();
