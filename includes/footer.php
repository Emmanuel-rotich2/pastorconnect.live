
<footer class="text-center py-3">

    <p class="mb-1">
        © 2026 FGCK Joyland. All rights reserved.
    </p>

    <p class="mb-0">
        Powered by
        <a
            href="/staff/dashboard#"
            title="MylesHub Technologies"
            style="font-weight: 700; text-decoration: none;"
        >
            @MylesHubTechnologies
        </a>
    </p>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* Accessible show/hide control for password fields that do not already have one. */
.fgck-password-toggle-wrap{position:relative;width:100%;}
.fgck-password-toggle-wrap > .fgck-password-toggle-input{padding-right:3rem;}
.fgck-password-toggle{
    position:absolute;right:.45rem;top:50%;transform:translateY(-50%);
    border:0;background:transparent;color:#64748b;cursor:pointer;
    width:2.25rem;height:2.25rem;border-radius:.5rem;
    display:inline-flex;align-items:center;justify-content:center;
    z-index:3;
}
.fgck-password-toggle:hover{background:rgba(100,116,139,.10);color:#1e293b;}
.fgck-password-toggle:focus-visible{outline:2px solid #4f46e5;outline-offset:2px;}
</style>
<script>
(function(){
    function addPasswordToggle(input){
        if(!input || input.dataset.fgckPasswordReady === '1') return;
        if(input.type !== 'password') return;
        if(input.closest('.fgck-input-wrap, .password-wrapper, .input-group')) return;
        if(input.nextElementSibling && input.nextElementSibling.classList.contains('fgck-password-toggle')) return;

        const wrapper=document.createElement('div');
        wrapper.className='fgck-password-toggle-wrap';
        input.parentNode.insertBefore(wrapper,input);
        wrapper.appendChild(input);
        input.classList.add('fgck-password-toggle-input');
        input.dataset.fgckPasswordReady='1';

        const button=document.createElement('button');
        button.type='button';
        button.className='fgck-password-toggle';
        button.setAttribute('aria-label','Show password');
        button.setAttribute('title','Show password');
        button.innerHTML='<i class="bi bi-eye" aria-hidden="true"></i>';
        button.addEventListener('click',function(){
            const showing=input.type === 'text';
            input.type=showing ? 'password' : 'text';
            button.setAttribute('aria-label',showing ? 'Show password' : 'Hide password');
            button.setAttribute('title',showing ? 'Show password' : 'Hide password');
            button.innerHTML='<i class="bi '+(showing ? 'bi-eye' : 'bi-eye-slash')+'" aria-hidden="true"></i>';
            input.focus();
        });
        wrapper.appendChild(button);
    }

    function initPasswordToggles(){
        document.querySelectorAll('input[type="password"]').forEach(addPasswordToggle);
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded',initPasswordToggles);
    }else{
        initPasswordToggles();
    }
})();
</script>

<div class="sidebar-overlay" data-sidebar-overlay></div>
<script>
(function(){
    const sidebar=document.getElementById('sidebar');
    const overlay=document.querySelector('[data-sidebar-overlay]');
    const openers=document.querySelectorAll('[data-sidebar-toggle]');
    function openSidebar(){sidebar?.classList.add('show');overlay?.classList.add('show');document.body.classList.add('sidebar-open');}
    function closeSidebar(){sidebar?.classList.remove('show');overlay?.classList.remove('show');document.body.classList.remove('sidebar-open');}
    openers.forEach(b=>b.addEventListener('click',()=>sidebar?.classList.contains('show')?closeSidebar():openSidebar()));
    overlay?.addEventListener('click',closeSidebar);
    sidebar?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{if(window.innerWidth<=900)closeSidebar();}));
    window.addEventListener('resize',()=>{if(window.innerWidth>900)closeSidebar();});
})();
</script>
