/* Command Palette (Ctrl/Cmd+K) - lightweight global actions & search */
(function(){
  var root = document.getElementById('cmdk');
  if(!root) return;
  var input = document.getElementById('cmdk-input');
  var results = document.getElementById('cmdk-results');
  var open = false;

  function show(){ if(!open){ root.classList.remove('hidden'); input.value=''; render([]); input.focus(); open=true; } }
  function hide(){ if(open){ root.classList.add('hidden'); open=false; } }

  // Toggle via button and backdrop
  var btn = document.getElementById('cmdk-button');
  if(btn){ btn.addEventListener('click', show); }
  root.addEventListener('click', function(e){ if(e.target && e.target.getAttribute('data-cmdk-close') !== null){ hide(); }});

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e){
    var isMac = navigator.platform.toUpperCase().indexOf('MAC')>=0;
    if((isMac && e.metaKey && e.key.toLowerCase()==='k') || (!isMac && e.ctrlKey && e.key.toLowerCase()==='k')){
      e.preventDefault(); show();
    } else if(e.key === 'Escape'){ hide(); }
  });

  // Data sources (extend later with server search)
  var staticItems = [
    {label:'Yeni İş', icon:'fa-briefcase', href: base_url('/jobs/new')},
    {label:'Yeni Müşteri', icon:'fa-user-plus', href: base_url('/customers/new')},
    {label:'Yeni Sözleşme', icon:'fa-file-contract', href: base_url('/contracts/new')},
    {label:'Dosya Yükle', icon:'fa-upload', href: base_url('/file-upload/form')},
    {label:'Takvim', icon:'fa-calendar', href: base_url('/calendar')},
    {label:'Analytics', icon:'fa-analytics', href: base_url('/analytics')},
    {label:'Performans', icon:'fa-tachometer-alt', href: base_url('/performance')},
  ];

  // Index navigation links from the header to power client-side search
  var navIndex = [];
  function indexNavigation(){
    try{
      var anchors = document.querySelectorAll('nav a[href]');
      navIndex = Array.prototype.slice.call(anchors).map(function(a){
        var label = (a.textContent || '').trim().replace(/\s+/g,' ');
        var href = a.getAttribute('href');
        if(!href || href === '#' || href.indexOf('javascript:') === 0) return null;
        return {label: label, icon:'fa-link', href: href};
      }).filter(Boolean);
    }catch(e){ navIndex = []; }
  }
  document.addEventListener('DOMContentLoaded', indexNavigation);

  function items(){ return staticItems.concat(navIndex); }

  function base_url(path){
    var base = (window.APP_BASE || '<?= addslashes(APP_BASE ?? "") ?>') || '';
    return base + path;
  }

  function render(list){
    if(!results) return;
    if(!list || list.length===0){
      results.innerHTML = '<div class="p-4 text-sm text-gray-500 dark:text-gray-400">Aramak için yazın; ok tuşlarıyla gezin, Enter ile seçin.</div>';
      return;
    }
    var html = '<ul class="divide-y divide-gray-100 dark:divide-gray-700">' + list.map(function(it, idx){
      return '<li class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center" data-idx="'+idx+'">\
        <i class="fas '+it.icon+' w-5 mr-2 text-primary-600"></i>\
        <span class="text-sm text-gray-800 dark:text-gray-100">'+escapeHtml(it.label)+'</span>\
      </li>';
    }).join('') + '</ul>';
    results.innerHTML = html;
  }

  function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]); }); }

  var current = 0; var filtered = items();
  function applySelection(){
    var lis = results.querySelectorAll('[data-idx]');
    lis.forEach(function(li){ li.classList.remove('bg-gray-100','dark:bg-gray-700'); });
    if(lis[current]) lis[current].classList.add('bg-gray-100','dark:bg-gray-700');
  }

  input.addEventListener('input', function(){
    var q = (input.value||'').toLowerCase();
    var all = items();
    filtered = all.filter(function(it){ return it.label.toLowerCase().indexOf(q) !== -1; })
                  .slice(0, 20);
    current = 0; render(filtered); applySelection();
    if(q.length>=2){
      fetch(base_url('/api/global-search?q=')+encodeURIComponent(q), { headers: { 'X-CSRF-Token': getCsrf() }}).then(function(r){return r.json();}).then(function(data){
        if(!data || !data.success) return;
        var apiItems = (data.results||[]).map(function(r){ return { label: r.title, icon: (r.icon||'fa-search'), href: r.url }; });
        filtered = apiItems.concat(filtered).slice(0, 20); render(filtered); applySelection();
      }).catch(function(){});
    }
  });

  function getCsrf(){
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  results.addEventListener('mouseover', function(e){
    var el = e.target.closest('[data-idx]'); if(!el) return; current = parseInt(el.getAttribute('data-idx'),10)||0; applySelection();
  });

  results.addEventListener('click', function(e){
    var el = e.target.closest('[data-idx]'); if(!el) return; var idx = parseInt(el.getAttribute('data-idx'),10)||0; var it = filtered[idx]; if(it){ window.location.href = it.href; }
  });

  input.addEventListener('keydown', function(e){
    if(e.key==='ArrowDown'){ e.preventDefault(); current = Math.min(current+1, Math.max(0, filtered.length-1)); applySelection(); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); current = Math.max(0, current-1); applySelection(); }
    else if(e.key==='Enter'){ e.preventDefault(); var it = filtered[current]; if(it){ window.location.href = it.href; hide(); } }
  });
})();


