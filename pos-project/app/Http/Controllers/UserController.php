<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\User;
use App\Mail\OTPMail;
use App\Helper\JWTToken;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;



class UserController extends Controller
{
     // Pages
     function LoginPage():View{
        return view('pages.auth.login-page');
    }// end method
    function RegistrationPage():View{
        return view('pages.auth.registration-page');
    }// end method

    function SendOtpPage():View{
        return view('pages.auth.send-otp-page');
    }// end method
    function verifyOTPPage():View{
        return view('pages.auth.verify-otp-page');
    }// end method
    function ResetPasswordPage():View{
        return view('pages.auth.reset-pass-page');
    }// end method
    function ProfilePage():View{
        return view('pages.dashboard.profile-page');
    }
//Registration
   function UserRegistration(Request $request){
    try {
        User::create([
            'firstName' => $request->input('firstName'),
            'lastName' => $request->input('lastName'),
            'email' => $request->input('email'),
            'mobile' => $request->input('mobile'),
            'password' => $request->input('password')
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'User Registraion Successfully'
        ],201);

    } catch(Exception $e){
        return response()->json([
            'status' => 'failed',
            'message' => $e->getMessage() ,
        ],200);
    }
    
    }//end method

//Login
function UserLogin(Request $request){
    $count=User::where('email','=',$request->input('email'))
         ->where('password','=',$request->input('password'))
         ->select('id')->first();

    if($count!==null){
        // User Login-> JWT Token Issue
        $token=JWTToken::CreateToken($request->input('email'),$count->id);
        return response()->json([
            'status' => 'success',
            'message' => 'User Login Successful',
        ],200)->cookie('token',$token,60*24*30);
    }
    else{
        return response()->json([
            'status' => 'failed',
            'message' => 'unauthorized'
        ],200);

    }

 }// end method

 //Send Email
    function SendOTPCode(Request $request){
            
        $email = $request->input('email');
        $otp = rand(1000,9999);
        $count=User::where('email','=',$email)->count();

        if($count == 1){
            // OTP Email Address
            //OTP Code Inaert
            Mail::to($email)->send(new OTPMail($otp));

            User::where('email','=',$email)->update(['otp'=>$otp]);

            return response()->json([
                'status'=>'success',
                'message'=> '4 Digit OTP Code has been send to your email !'
            ],200);
        }else{
            return response()->json([
                'status'=>'failed',
                'message'=> 'unauthorized'
            ]);
        }
    }// end method

// Verifying the OTP
    function VerifyOTP(Request $request){
        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email','=',$email)
            ->where('otp','=',$otp)->count();

        if($count==1){
            // Database OTP Update
            User::where('email','=',$email)->update(['otp'=>'0']);

            // Password Reset Token Issue
            $token = JWTToken::CreateTokenForSetPassword($request->input('email'));
            return response()->json([
                'status'=>'success',
                'message'=> 'OTP Verification Successful',
               
            ],200)->cookie('token',$token,60*24*30);
            
        }else{
            return response()->json([
                'status'=>'failed',
                'message'=> 'unauthorized'
            ],401);
        }
    }// end method

    function ResetPassword(Request $request){
        try{
            $email=$request->header('email');
            $password=$request->input('password');
            User::where('email','=',$email)->update(['password'=>$password]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful',
            ],200);

        }catch (Exception $exception){
            return response()->json([
                'status' => 'fail',
                'message' => 'Something Went Wrong',
            ],200);
        }
    }// end method
    
//Logout
   function UserLogout(){
    return redirect('userLogin')->cookie('token','',-1);
   }

//User profile 
   function UserProfile(Request $request){
    $email=$request->header('email');
    $user=User::where('email','=',$email)->first();
    return response()->json([
        'status' => 'success',
        'message' => 'Request Successful',
        'data' => $user
    ],200);
}

//Updating profile
function UpdateProfile(Request $request){
    try{
        $email=$request->header('email');
        $firstName=$request->input('firstName');
        $lastName=$request->input('lastName');
        $mobile=$request->input('mobile');
        $password=$request->input('password');
        User::where('email','=',$email)->update([
            'firstName'=>$firstName,
            'lastName'=>$lastName,
            'mobile'=>$mobile,
            'password'=>$password
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Request Successful',
        ],200);

    }catch (Exception $exception){
        return response()->json([
            'status' => 'fail',
            'message' => 'Something Went Wrong',
        ],200);
    }
}

}
