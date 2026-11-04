(function(){
var d=document;
d.addEventListener('click',function(ev){
  var t=ev.target.closest('[data-toggle-menu]');if(t){d.body.classList.toggle('menu-open');return}
  var c=ev.target.closest('[data-confirm]');if(c&&!confirm(c.getAttribute('data-confirm'))){ev.preventDefault();return}
  var a=ev.target.closest('.att-btn');if(a){ev.preventDefault();cycle(a);return}
  var all=ev.target.closest('[data-all]');if(all){ev.preventDefault();d.querySelectorAll('.att-btn').forEach(function(b){set(b,all.getAttribute('data-all'))});dirty=true}
});
d.addEventListener('change',function(ev){
  var s=ev.target.closest('[data-autosubmit]');if(s)s.form.submit();
  if(ev.target.closest('form[data-dirty]'))dirty=true;
  if(ev.target.classList.contains('nota'))markNota(ev.target);
});
d.addEventListener('input',function(ev){if(ev.target.closest('form[data-dirty]'))dirty=true});
// frequência: P -> F -> FJ -> P
function set(b,v){var i=b.nextElementSibling;b.classList.remove('f','j');if(v==='F'){b.classList.add('f');i.value='1'}else if(v==='J'){b.classList.add('j');i.value='J'}else{i.value='0'}b.textContent=v==='J'?'FJ':v}
function cycle(b){var i=b.nextElementSibling;set(b,i.value==='0'?'F':i.value==='1'?'J':'P');dirty=true}
// notas
var max=parseFloat((d.querySelector('[data-max]')||{}).dataset?.max||10),min=parseFloat((d.querySelector('[data-media]')||{}).dataset?.media||6);
function markNota(el){var v=parseFloat(el.value.replace(',','.'));if(isNaN(v)){el.classList.remove('low');return}if(v>max){v=max;el.value=String(v).replace('.',',')}if(v<0)el.value='0';el.classList.toggle('low',v<min)}
d.querySelectorAll('input.nota').forEach(markNota);
// aviso de alterações não salvas
var dirty=false;d.querySelectorAll('form[data-dirty]').forEach(function(f){f.addEventListener('submit',function(){dirty=false})});
window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue=''}});
// CEP (ViaCEP)
d.querySelectorAll('input[data-cep]').forEach(function(i){i.addEventListener('blur',function(){var c=i.value.replace(/\D/g,'');if(c.length!==8)return;
  fetch('https://viacep.com.br/ws/'+c+'/json/').then(function(r){return r.json()}).then(function(j){if(j.erro)return;var f=i.form;
  if(f.endereco&&!f.endereco.value)f.endereco.value=j.logradouro;if(f.bairro&&!f.bairro.value)f.bairro.value=j.bairro;if(f.cidade&&!f.cidade.value)f.cidade.value=j.localidade+'/'+j.uf}).catch(function(){})})});
// máscaras simples
var masks={cpf:[/(\d{3})(\d{3})(\d{3})(\d{0,2})/,'$1.$2.$3-$4',11],cep:[/(\d{5})(\d{0,3})/,'$1-$2',8],tel:[/(\d{2})(\d{4,5})(\d{0,4})/,'($1) $2-$3',11]};
d.querySelectorAll('input[data-mask]').forEach(function(i){var m=masks[i.dataset.mask];i.addEventListener('input',function(){var v=i.value.replace(/\D/g,'').slice(0,m[2]);if(i.dataset.mask==='tel'&&v.length===10)v=v.replace(/(\d{2})(\d{4})(\d{4})/,'($1) $2-$3');else if(v.length>=(i.dataset.mask==='cep'?6:4))v=v.replace(m[0],m[1]);i.value=v.replace(/[-.\s()]+$/,'')})});
// filtro rápido de tabela
d.querySelectorAll('[data-filter]').forEach(function(i){var tb=d.querySelector(i.dataset.filter);i.addEventListener('input',function(){var q=i.value.toLowerCase();tb.querySelectorAll('tbody tr').forEach(function(r){r.style.display=r.textContent.toLowerCase().indexOf(q)>-1?'':'none'})})});
})();
