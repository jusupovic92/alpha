document.querySelectorAll('[data-personal-sale-fields]').forEach(fields => {
  const reason = fields.closest('form').querySelector('[name="reason"]');
  function update() {
    const sale = reason.value === 'personal_sale';
    fields.hidden = !sale;
    fields.querySelectorAll('input,select').forEach(input => { input.disabled = !sale; });
    fields.querySelector('[name="recipient"]').required = sale;
    fields.querySelector('[name="unit_price"]').required = sale;
  }
  reason.addEventListener('change', update); update();
});

document.querySelectorAll('[data-shipment-plan]').forEach(form=>{form.querySelector('[data-delivery-choice]').addEventListener('change',event=>{const option=event.target.selectedOptions[0];if(!option.value)return;for(const [field,key] of Object.entries({courier_name:'courier',delivery_cost:'cost',delivery_terms:'terms',delivery_payer:'payer'}))form.querySelector('[name='+field+']').value=option.dataset[key]||'';});});
