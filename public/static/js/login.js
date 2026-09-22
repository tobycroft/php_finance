/**
 * 登录页脚本：滑动拼图验证码 + 提交登录、保存 Token（localStorage + Cookie）
 * 后续 AJAX 请求请在 Header 中携带 token 字段
 */
(function () {
    var btn = document.getElementById('login-btn');
    var errorBox = document.getElementById('login-error');

    // 滑块验证码元素
    var slideInner = document.getElementById('slide-inner');
    var slideBg = document.getElementById('slide-bg');
    var slideBlock = document.getElementById('slide-block');
    var slideHandle = document.getElementById('slide-handle');
    var slideBarText = document.getElementById('slide-bar-text');
    var slideStatus = document.getElementById('slide-status');

    var captcha = {
        ident: '',
        data: null,
        pass: '',
        dragging: false
    };

    function showError(msg) {
        errorBox.textContent = msg || '';
    }

    function showSlideStatus(msg, type) {
        slideStatus.textContent = msg || '';
        slideStatus.className = 'slide-status' + (type ? ' ' + type : '');
    }

    /* ------------------------------ 滑动验证码 ------------------------------ */
    function refreshCaptcha() {
        captcha.ident = '';
        captcha.data = null;
        captcha.pass = '';
        slideBlock.style.display = 'none';
        slideHandle.style.left = '0px';
        slideHandle.classList.remove('success', 'error');
        slideBarText.style.visibility = 'visible';
        showSlideStatus('验证码加载中...');

        fetch('/captcha/slide/create', { method: 'POST' })
            .then(function (res) { return res.json(); })
            .then(function (ret) {
                if (ret.code !== 0 || !ret.data || !ret.data.ident) {
                    showSlideStatus(ret.msg || '验证码加载失败', 'error');
                    return;
                }
                renderCaptcha(ret.data);
            })
            .catch(function () {
                showSlideStatus('验证码加载失败，请刷新页面重试', 'error');
            });
    }

    function renderCaptcha(result) {
        captcha.ident = result.ident;
        captcha.data = result.data;
        captcha.data.pad_top = captcha.data.pad_top || 0;
        captcha.data.pad_left = captcha.data.pad_left || 0;

        slideBg.src = captcha.data.bg;
        slideBlock.style.width = captcha.data.block_size + 'px';
        slideBlock.style.height = captcha.data.block_size + 'px';
        slideBlock.style.top = (captcha.data.y - captcha.data.pad_top) + 'px';
        slideBlock.onload = function () {
            slideBlock.style.display = 'block';
        };
        slideBlock.src = captcha.data.block;

        slideHandle.style.left = '0px';
        slideHandle.classList.remove('success', 'error');
        slideBarText.style.visibility = 'visible';
        showSlideStatus('');
    }

    function dragStart(e) {
        if (!captcha.data || captcha.pass || captcha.dragging) {
            return;
        }
        captcha.dragging = true;
        captcha.startX = e.clientX || (e.touches && e.touches[0].clientX);
        captcha.startLeft = parseInt(slideHandle.style.left) || 0;
        slideBarText.style.visibility = 'hidden';
        e.preventDefault();
    }

    function dragMove(e) {
        if (!captcha.dragging || !captcha.data) {
            return;
        }
        var clientX = e.clientX || (e.touches && e.touches[0].clientX);
        var deltaX = clientX - captcha.startX;
        var maxLeft = captcha.data.bg_width - captcha.data.block_size;
        var newLeft = Math.max(0, Math.min(captcha.startLeft + deltaX, maxLeft));
        slideHandle.style.left = newLeft + 'px';
        slideBlock.style.left = (newLeft - captcha.data.pad_left) + 'px';
    }

    function dragEnd() {
        if (!captcha.dragging) {
            return;
        }
        captcha.dragging = false;
        if (!captcha.data) {
            return;
        }

        var finalX = parseInt(slideHandle.style.left) || 0;
        if (finalX <= 0) {
            slideBarText.style.visibility = 'visible';
            return;
        }

        fetch('/captcha/slide/check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ident: captcha.ident, x: finalX })
        }).then(function (res) { return res.json(); }).then(function (ret) {
            if (ret.code === 0 && ret.data && ret.data.pass) {
                captcha.pass = ret.data.pass;
                slideHandle.classList.add('success');
                showSlideStatus('验证成功', 'success');
            } else {
                slideHandle.classList.add('error');
                showSlideStatus(ret.msg || '验证失败，正在刷新...', 'error');
                setTimeout(refreshCaptcha, 1000);
            }
        }).catch(function () {
            showSlideStatus('网络异常，正在刷新...', 'error');
            setTimeout(refreshCaptcha, 1000);
        });
    }

    slideHandle.addEventListener('mousedown', dragStart);
    document.addEventListener('mousemove', dragMove);
    document.addEventListener('mouseup', dragEnd);
    slideHandle.addEventListener('touchstart', dragStart, { passive: false });
    document.addEventListener('touchmove', dragMove, { passive: true });
    document.addEventListener('touchend', dragEnd);

    /* ------------------------------ 登录 ------------------------------ */
    function saveToken(token) {
        // localStorage 供 AJAX 读取并写入 Header
        localStorage.setItem('token', token);
        // Cookie 兜底：页面跳转时随 Header 自动携带，保证服务端渲染页面可鉴权
        document.cookie = 'token=' + encodeURIComponent(token) + '; path=/; max-age=' + (7 * 24 * 3600) + '; SameSite=Lax';
    }

    function doLogin() {
        if (!captcha.pass) {
            showError('请先拖动滑块完成安全验证');
            return;
        }

        var username = document.getElementById('username').value.trim();
        var password = document.getElementById('password').value;
        if (!username || !password) {
            showError('请输入用户名和密码');
            return;
        }

        btn.disabled = true;
        btn.textContent = '登录中...';

        fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                password: password,
                captcha_pass: captcha.pass
            })
        }).then(function (res) {
            return res.json();
        }).then(function (ret) {
            if (ret.code === 0 && ret.data && ret.data.token) {
                saveToken(ret.data.token);
                window.location.href = '/panel';
                return;
            }
            showError(ret.msg || '登录失败，请稍后再试');
            if (ret.code === 2) {
                // 凭据失效，刷新验证码重新验证
                captcha.pass = '';
                refreshCaptcha();
            }
        }).catch(function () {
            showError('网络异常，请稍后再试');
        }).finally(function () {
            btn.disabled = false;
            btn.textContent = '登 录';
        });
    }

    btn.addEventListener('click', doLogin);
    document.getElementById('password').addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            doLogin();
        }
    });

    refreshCaptcha();
})();
