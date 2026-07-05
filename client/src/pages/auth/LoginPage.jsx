import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useNavigate, Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
import api from '../../api/axios';
import { GraduationCap, Mail, Lock, Loader2, ArrowRight } from 'lucide-react';

const LoginPage = () => {
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login } = useAuth();
  const { register, handleSubmit, formState: { errors } } = useForm();

  const onSubmit = async (data) => {
    setLoading(true);
    try {
      // Staff/Admin Login
      const res = await api.post('/auth/login', {
        email: data.email,
        password: data.password
      });

      toast.success('Login successful!');
      login(res.data.data, res.data.token);
      navigate('/dashboard');
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
            <div className="mx-auto w-16 h-16 bg-indigo-600/20 rounded-2xl flex items-center justify-center mb-6 border border-indigo-500/20">
              <GraduationCap className="w-10 h-10 text-indigo-500" />
            </div>
            <h1 className="text-3xl font-bold text-white tracking-tight">
              Staff Portal
            </h1>
            <p className="mt-2 text-slate-400">
              Enter your credentials to access the admin & staff portal
            </p>
          </div>

          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-8">
            <div className="form-group relative">
              <label className="label">Email Address</label>
              <div className="relative">
                <Mail className="absolute left-3.5 top-3 h-5 w-5 text-slate-500" />
                <input
                  type="email"
                  {...register("email", { required: "Email is required" })}
                  className={`input pl-11 ${errors.email ? 'input-error' : ''}`}
                  placeholder="john@example.com"
                />
              </div>
              {errors.email && <span className="error-msg">{errors.email.message}</span>}
            </div>

            <div className="form-group relative">
              <div className="flex justify-between items-center mb-1.5">
                <label className="label !mb-0">Password</label>
                <Link to="/forgot-password" className="text-sm text-indigo-400 hover:text-indigo-300">
                  Forgot password?
                </Link>
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

            <button
              type="submit"
              disabled={loading}
              className="btn-primary w-full h-11 text-base shadow-glow flex items-center justify-center group"
            >
              {loading ? (
                <Loader2 className="w-5 h-5 animate-spin" />
              ) : (
                <>
                  Sign In
                  <ArrowRight className="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                </>
              )}
            </button>
          </form>

          <p className="text-center text-sm text-slate-400 mt-6">
            Are you a student?{' '}
            <Link to="/student-login" className="text-emerald-400 hover:text-emerald-300 font-medium">
              Go to Student Login
            </Link>
          </p>

          <p className="text-center text-sm text-slate-500 mt-4">
            Don't have an account?{' '}
            <Link to="/register" className="text-indigo-400 hover:text-indigo-300 font-medium">
              Register here
            </Link>
          </p>
        </div>
      </div>

      {/* Right side — hero visual */}
      <div className="hidden lg:flex lg:w-1/2 relative bg-surface-card overflow-hidden items-center justify-center">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-900/20 to-slate-900 z-10" />
        
        {/* Abstract decorative elements */}
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-[100px]" />
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-600/20 rounded-full blur-[100px]" />
        
        <div className="relative z-20 max-w-lg p-12 text-center">
          <h2 className="text-4xl font-bold text-white mb-6 leading-tight">
            Managing education has never been <span className="gradient-text">easier</span>
          </h2>
          <p className="text-slate-300 text-lg leading-relaxed">
            Access your courses, track attendance, and stay updated with your academic progress all in one place.
          </p>
          
          <div className="mt-12 grid grid-cols-2 gap-4">
            <div className="bg-slate-800/50 backdrop-blur-md p-4 rounded-2xl border border-slate-700/50">
              <div className="text-3xl font-bold text-indigo-400 mb-1">99%</div>
              <div className="text-sm text-slate-400">System Uptime</div>
            </div>
            <div className="bg-slate-800/50 backdrop-blur-md p-4 rounded-2xl border border-slate-700/50">
              <div className="text-3xl font-bold text-purple-400 mb-1">24/7</div>
              <div className="text-sm text-slate-400">Access to Portal</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
