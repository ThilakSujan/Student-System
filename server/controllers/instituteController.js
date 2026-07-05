const InstituteProfile = require('../models/InstituteProfile');
const AppError = require('../utils/AppError');

exports.getInstitute = async (req, res, next) => {
  try {
    const data = await InstituteProfile.findOne();
    res.status(200).json({ status: 'success', data: data || {} });
  } catch (error) { next(error); }
};

exports.updateInstitute = async (req, res, next) => {
  try {
    let data = await InstituteProfile.findOne();
    if (data) {
      data = await InstituteProfile.findByIdAndUpdate(data._id, req.body, { new: true });
    } else {
      data = await InstituteProfile.create(req.body);
    }
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};
