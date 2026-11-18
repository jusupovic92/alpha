document.querySelectorAll('[data-delivery-method-editor]').forEach(editor=>{
  const choice=editor.querySelector('[data-method-type]'),fields=editor.querySelector('[data-courier-fields]'),info=editor.querySelector('[data-pickup-info]'),name=editor.querySelector('[name=name]'),cost=editor.querySelector('[name=cost]');
  function update(){const pickup=choice.value==='pickup';fields.hidden=pickup;fields.disabled=pickup;info.hidden=!pickup;cost.required=!pickup;if(pickup&&!name.value.trim())name.value='Lično preuzimanje';}
  choice.addEventListener('change',update);update();
});
const partnerDelivery=document.querySelector('.b2b-portal select#shipping');
partnerDelivery?.addEventListener('change',()=>partnerDelivery.form.requestSubmit());
