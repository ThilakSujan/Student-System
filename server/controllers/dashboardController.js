const Student = require('../models/Student');
const Class = require('../models/Class');
const User = require('../models/User');
const Attendance = require('../models/Attendance');
const FeePayment = require('../models/FeePayment');

exports.getDashboardStats = async (req, res, next) => {
  try {
    const role = req.userRole;
    let data = {};

    if (role === 'admin' || role === 'staff') {
      const totalStudents = await Student.countDocuments({ status: 'Active' });
      const totalClasses = await Class.countDocuments({ status: 'Active' });
      const totalStaff = await User.countDocuments({ role: 'staff', account_status: 'Approved' });
      
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);
      
      const todayAttendance = await Attendance.countDocuments({
        date: { $gte: today, $lt: tomorrow },
        status: 'Present'
      });

      const attendancePercentage = totalStudents > 0 ? ((todayAttendance / totalStudents) * 100).toFixed(1) : 0;
      
      const payments = await FeePayment.aggregate([
        { $match: { createdAt: { $gte: new Date(today.getFullYear(), today.getMonth(), 1) } } },
        { $group: { _id: null, total: { $sum: '$amount_paid' } } }
      ]);
      const monthlyRevenue = payments.length > 0 ? payments[0].total : 0;

      data = {
        totalStudents,
        totalClasses,
        totalStaff,
        attendancePercentage,
        monthlyRevenue
      };
    } else if (role === 'student') {
      const studentId = req.user.id;
      
      const myClasses = await Class.find({ students: studentId });
      const totalAttendance = await Attendance.countDocuments({ student: studentId });
      const presentAttendance = await Attendance.countDocuments({ student: studentId, status: 'Present' });
      
      const myAttendancePercentage = totalAttendance > 0 ? ((presentAttendance / totalAttendance) * 100).toFixed(1) : 0;

      data = {
        enrolledClasses: myClasses.length,
        attendancePercentage: myAttendancePercentage,
      };
    }

    res.status(200).json({ status: 'success', data });
  } catch (error) {
    next(error);
  }
};
