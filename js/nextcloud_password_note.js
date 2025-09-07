(function(){
  function ready(cb){if(document.readyState==='complete'||document.readyState==='interactive')return cb();document.addEventListener('DOMContentLoaded',cb,{once:true});}
  function mk(t,c){var d=document.createElement(t);if(c)d.className=c;return d;}

  function findPasswordInputs(){
    var inputs=[].slice.call(document.querySelectorAll('input[type="password"]'));
    inputs.sort(function(a,b){
      function score(el){
        var n=(el.getAttribute('name')||'').toLowerCase();
        var i=(el.id||'').toLowerCase();
        var s=0;
        if(n.includes('conf')||n.includes('confirm')||i.includes('conf')||i.includes('confirm')) s+=4;
        if(n.includes('new')||i.includes('new')) s+=2;
        if(n.includes('cur')||n.includes('old')||i.includes('cur')||i.includes('old')) s-=3;
        return s;
      }
      return score(b)-score(a);
    });
    return inputs;
  }

  ready(function(){
    try{
      var env=(window.rcmail&&rcmail.env)||{};
      var taskOk=env.task==='settings';
      var action=env.action||(new URLSearchParams(window.location.search)).get('_action')||'';
      var onPwd=action.indexOf('plugin.password')===0 || action==='password';
      var enabled=env.nextcloud_pw_note_enabled;
      if(!enabled||!taskOk||!onPwd) return;

      var labels=(env.nextcloud_labels)||{};
      var noteText=labels.nc_reminder||'Remember to change Nextcloud password';

      // Preferred anchor: the confirm password field with name="_confpasswd"
      var anchor=document.querySelector('input[name="_confpasswd"]');

      // Fallbacks if that exact field is missing
      if(!anchor){
        var inputs=findPasswordInputs();
        if(inputs && inputs.length) anchor=inputs[inputs.length-1];
      }

      var parent=(anchor && anchor.parentElement) || document.querySelector('#preferences, #password-form, form, body') || document.body;
      var note=mk('div','nextcloud-pw-note');
      note.textContent=noteText;

      if(anchor && anchor.nextSibling) parent.insertBefore(note, anchor.nextSibling);
      else parent.appendChild(note);
    }catch(e){ /* no-op */ }
  });
})();