import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useNavigate, Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import api from '../../api/axios';
import { GraduationCap, Mail, Lock, Loader2, ArrowRight } from 'lucide-react';

const StudentLoginPage = () => {
  const [loading, setLoading] = useState(false);
  const [firstLoginMode, setFirstLoginMode] = useState(false);
  const [firstLoginData, setFirstLoginData] = useState(null);
  const navigate = useNavigate();
  const { login } = useAuth();
  const { register, handleSubmit, formState: { errors } } = useForm();

  const onSubmit = async (data) => {
    setLoading(true);
    try {
      if (firstLoginMode) {
        if (data.password !== data.confirmPassword) {
          toast.error("Passwords do not match!");
          setLoading(false);
          return;
        }
        
        const res = await api.post('/auth/first-login', {
          email: firstLoginData.email,
          password: data.password
        });
        
        toast.success("Password set successfully!");
        login(res.data.data, res.data.token);
        navigate('/dashboard');
        return;
      }

      // Student Login
      const res = await api.post('/auth/student-login', {
        email: data.email,
        password: data.password
      });

      if (res.data.requiresPasswordSetup) {
        setFirstLoginData({ email: res.data.email, studentId: res.data.studentId });
        setFirstLoginMode(true);
        toast('Welcome! Please set your password.', { icon: '👋' });
      } else {
        toast.success('Login successful!');
        login(res.data.data, res.data.token);
        navigate('/dashboard');
      }
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex bg-slate-950">
      {/* Left side — form */}
      <div className="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 relative z-10">
        <div className="w-full max-w-md space-y-8 animate-fade-in">
          
          <div className="text-center">
            <div className="mx-auto w-16 h-16 bg-emerald-600/20 rounded-2xl flex items-center justify-center mb-6 border border-emerald-500/20">
              <GraduationCap className="w-10 h-10 text-emerald-500" />
            </div>
            <h1 className="text-3xl font-bold text-white tracking-tight">
              {firstLoginMode ? 'Set Your Password' : 'Student Portal'}
            </h1>
            <p className="mt-2 text-slate-400">
              {firstLoginMode ? 'Complete your first login setup' : 'Enter your credentials to access the student portal'}
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-8">
            {!firstLoginMode ? (
              <>
                <div className="form-group relative">
                  <label className="label">Student Email</label>
                  <div className="relative">
                    <Mail className="absolute left-3.5 top-3 h-5 w-5 text-slate-500" />
                    <input
                      type="email"
                      {...register("email", { required: "Email is required" })}
                      className={`input pl-11 ${errors.email ? 'input-error' : ''}`}
                      placeholder="student@example.com"
                    />
                  </div>
                  {errors.email && <span className="error-msg">{errors.email.message}</span>}
                </div>

                <div className="form-group relative">
                  <div className="flex justify-between items-center mb-1.5">
                    <label className="label !mb-0">Password</label>
                  </div>
                  <div className="relative">
                    <Lock className="absolute left-3.5 top-3 h-5 w-5 text-slate-500" />
                    <input
                      type="password"
                      {...register("password", { required: "Password is required" })}
                      className={`input pl-11 ${errors.password ? 'input-error' : ''}`}
                      placeholder="••••••••"
                    />
                  </div>
                  {errors.password && <span className="error-msg">{errors.password.message}</span>}
                </div>
              </>
            ) : (
              <>
                <div className="form-group relative">
                  <label className="label">New Password</label>
                  <div className="relative">
                    <Lock className="absolute left-3.5 top-3 h-5 w-5 text-slate-500" />
                    <input
                      type="password"
                      {...register("password", { 
                        required: "Password is required",
                        minLength: { value: 6, message: "Minimum 6 characters" }
                      })}
                      className={`input pl-11 ${errors.password ? 'input-error' : ''}`}
                      placeholder="••••••••"
                    />
                  </div>
                  {errors.password && <span className="error-msg">{errors.password.message}</span>}
                </div>

                <div className="form-group relative">
                  <label className="label">Confirm New Password</label>
                  <div className="relative">
                    <Lock className="absolute left-3.5 top-3 h-5 w-5 text-slate-500" />
                    <input
                      type="password"
                      {...register("confirmPassword", { required: "Please confirm password" })}
                      className={`input pl-11 ${errors.confirmPassword ? 'input-error' : ''}`}
                      placeholder="••••••••"
                    />
                  </div>
                  {errors.confirmPassword && <span className="error-msg">{errors.confirmPassword.message}</span>}
                </div>
              </>
            )}

            <button
              type="submit"
              disabled={loading}
              className="btn-primary w-full h-11 text-base shadow-glow flex items-center justify-center group !bg-emerald-600 hover:!bg-emerald-500 focus:ring-emerald-500/50 border-none"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>
                  {firstLoginMode ? 'Save & Continue' : 'Sign In as Student'}
                  <ArrowRight className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                </>
              )}
            </button>
          </form>

          {!firstLoginMode && (
            <p className="text-center text-sm text-slate-400 mt-6">
              Are you a staff member?{' '}
              <Link to="/login" className="text-emerald-400 hover:text-emerald-300 font-medium">
                Go to Staff Login
              </Link>
            </p>
          )}
        </div>
      </div>

      {/* Right side — hero visual */}
      <div className="hidden lg:flex lg:w-1/2 relative bg-surface-card overflow-hidden items-center justify-center">
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-900/20 to-slate-900 z-10" />
        
        {/* Abstract decorative elements */}
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-emerald-600/20 rounded-full blur-[100px]" />
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-teal-600/20 rounded-full blur-[100px]" />
        
        <div className="relative z-20 max-w-lg p-12 text-center">
          <h2 className="text-4xl font-bold text-white mb-6 leading-tight">
            Your education journey <span className="text-emerald-400">starts here</span>
          </h2>
          <p className="text-slate-300 text-lg leading-relaxed">
            Access your courses, track attendance, and view your report cards all in one student dashboard.
          </p>
        </div>
      </div>
    </div>
  );
};

export default StudentLoginPage;
