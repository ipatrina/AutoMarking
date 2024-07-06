<!DOCTYPE html>
<html>
<head>
    <title>AutoMarking</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            margin: 50px auto;
            max-width: 500px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin: 20px;
            color: #333333;
        }
        
        h5 {
            margin: 20px;
            color: #cc0000;
        }

        form {
            padding: 20px;
            border-radius: 5px;
            background-color: #f2f2f2;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: #333333;
            font-weight: bold;
        }

        input[type=text], input[type=password] {
            width: 100%;
            padding: 12px 20px;
            margin: 8px 0;
            box-sizing: border-box;
            border: none;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #0074d9;
            color: #ffffff;
            padding: 14px 20px;
            margin-top: 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .error {
            background-color: #0052a5;
            color: #cc0000;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AutoMarking SSO Login</h1>
        <h5>Important: This page is for single sign-on demonstration purposes only. For production use, this system should be connected to your own user authentication system. In this demo page, user password is identical to the email address.</h5>
        <form>
            <label for="username">Email Address:</label>
            <input type="text" id="username" placeholder="Enter your email address" required>

            <label for="password">Password:</label>
            <input type="password" id="password" placeholder="Enter your password" required>

            <button type="button" onclick="login()">Login</button>

            <div id="error" class="error" style="display: none;">
                Invalid email address or password.
            </div>
        </form>
    </div>
    <script>
        function login() {
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;
            var error = document.getElementById("error");

            if(username === password) {
                var sso_u = username;
                var sso_n = '';
                if (sso_u.indexOf('@') > -1) {
                    sso_n = sso_u.split('@')[0];
                } else {
                    sso_n = sso_u;
                }
                sso_n = sso_n.split(/[^a-zA-Z]/)
                    .filter(function(word) {
                    return word !== "";
                })
                    .map(function(word) {
                    return word.charAt(0).toUpperCase() + word.slice(1);
                })
                    .join(" ");
                sso_n = sso_n.replace(/\./g, "");
                var sso_t = 86400;
                var sso_k = sha1(sso_u + sso_n + sso_t + "sso_secret");
                var url = "login.php?sso_u=" + sso_u + "&sso_n=" + sso_n + "&sso_t=" + sso_t + "&sso_k=" + sso_k;
                window.location.href = url;
            } else {
                error.style.display = "block";
            }
        }

        sha1 = function(r) {
            var t = function() {
                function r() {
                    e[0] = 1732584193, e[1] = 4023233417, e[2] = 2562383102, e[3] = 271733878, e[4] = 3285377520, 
                        s = c = 0;
                }
                function t(r) {
                    var t, n, o, f, a, u, c, s;
                    for (t = i, n = 0; 64 > n; n += 4) t[n / 4] = r[n] << 24 | r[n + 1] << 16 | r[n + 2] << 8 | r[n + 3];
                    for (n = 16; 80 > n; n++) r = t[n - 3] ^ t[n - 8] ^ t[n - 14] ^ t[n - 16], t[n] = 4294967295 & (r << 1 | r >>> 31);
                    for (r = e[0], o = e[1], f = e[2], a = e[3], u = e[4], n = 0; 80 > n; n++) 40 > n ? 20 > n ? (c = a ^ o & (f ^ a), 
                                                                                                                  s = 1518500249) : (c = o ^ f ^ a, s = 1859775393) : 60 > n ? (c = o & f | a & (o | f), 
            s = 2400959708) : (c = o ^ f ^ a, s = 3395469782), c = (4294967295 & (r << 5 | r >>> 27)) + c + u + s + t[n] & 4294967295, 
                        u = a, a = f, f = 4294967295 & (o << 30 | o >>> 2), o = r, r = c;
                    e[0] = e[0] + r & 4294967295, e[1] = e[1] + o & 4294967295, e[2] = e[2] + f & 4294967295, 
                        e[3] = e[3] + a & 4294967295, e[4] = e[4] + u & 4294967295;
                }
                function n(r, n) {
                    if ("string" == typeof r) {
                        for (var o = [], e = 0, i = (r = unescape(encodeURIComponent(r))).length; e < i; ++e) o.push(r.charCodeAt(e));
                        r = o;
                    }
                    if (n || (n = r.length), o = 0, 0 == c) for (;o + 64 < n; ) t(r.slice(o, o + 64)), 
                        o += 64, s += 64;
                    for (;o < n; ) if (f[c++] = r[o++], s++, 64 == c) for (c = 0, t(f); o + 64 < n; ) t(r.slice(o, o + 64)), 
                        o += 64, s += 64;
                }
                function o() {
                    var r, o, i = [], u = 8 * s;
                    for (n(a, 56 > c ? 56 - c : 64 - (c - 56)), r = 63; 56 <= r; r--) f[r] = 255 & u, 
                        u >>>= 8;
                    for (t(f), r = u = 0; 5 > r; r++) for (o = 24; 0 <= o; o -= 8) i[u++] = e[r] >> o & 255;
                    return i;
                }
                var e, f, i, a, u, c, s;
                for (e = [], f = [], i = [], a = [ 128 ], u = 1; 64 > u; ++u) a[u] = 0;
                return r(), {
                    reset: r,
                    update: n,
                    digest: o,
                    digestString: function() {
                        for (var r = o(), t = "", n = 0; n < r.length; n++) t += "0123456789ABCDEF".charAt(Math.floor(r[n] / 16)) + "0123456789ABCDEF".charAt(r[n] % 16);
                        return t;
                    }
                };
            }();
            return t.update(r), t.digestString().toLowerCase();
        };
    </script>
</body>
</html>