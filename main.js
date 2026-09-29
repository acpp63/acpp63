// Menu mobile
const b=document.querySelector('.menu-btn'),n=document.querySelector('.nav');
if(b){b.addEventListener('click',()=>{const o=n.classList.toggle('open');b.setAttribute('aria-expanded',o);b.textContent=o?'Fermer':'Menu';});}

// Pièces jointes : liste des fichiers et contrôle des limites avant envoi
const fi=document.getElementById('fichiers'),li=document.getElementById('fic-liste');
if(fi&&li){
  const MAX_N=5, MAX_T=10*1024*1024;
  const OK=fi.accept.split(',').map(e=>e.trim().toLowerCase());
  const ko=v=>v<1048576?Math.round(v/1024)+' Ko':(v/1048576).toFixed(1).replace('.',',')+' Mo';
  const verifier=()=>{
    li.innerHTML='';let t=0,err='';
    [...fi.files].forEach(f=>{t+=f.size;const e='.'+f.name.split('.').pop().toLowerCase();
      if(!OK.includes(e))err='Format non accepté : '+f.name;
      const l=document.createElement('li');l.textContent=f.name+' — '+ko(f.size);li.appendChild(l);});
    if(fi.files.length>MAX_N)err='5 fichiers maximum.';
    else if(t>MAX_T)err='Taille totale '+ko(t)+' : 10 Mo maximum.';
    if(err){const l=document.createElement('li');l.className='err';l.textContent=err;li.appendChild(l);}
    fi.setCustomValidity(err);
  };
  fi.addEventListener('change',verifier);
  const z=fi.closest('.depot');
  ['dragenter','dragover'].forEach(e=>z.addEventListener(e,()=>z.classList.add('survol')));
  ['dragleave','drop'].forEach(e=>z.addEventListener(e,()=>z.classList.remove('survol')));
  // Bouton désactivé pendant l'envoi (les gros fichiers prennent quelques secondes)
  fi.form.addEventListener('submit',()=>{const s=fi.form.querySelector('button[type=submit]');s.disabled=true;s.textContent='Envoi en cours…';});
}
