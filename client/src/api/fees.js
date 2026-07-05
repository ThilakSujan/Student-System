import api from './axios';

export const getFeeCategories = () => api.get('/fees/categories');
export const createFeeCategory = (data) => api.post('/fees/categories', data);
export const updateFeeCategory = (id, data) => api.put(`/fees/categories/${id}`, data);
export const deleteFeeCategory = (id) => api.delete(`/fees/categories/${id}`);

export const getFeeStructures = () => api.get('/fees/structures');
export const createFeeStructure = (data) => api.post('/fees/structures', data);
export const updateFeeStructure = (id, data) => api.put(`/fees/structures/${id}`, data);
export const deleteFeeStructure = (id) => api.delete(`/fees/structures/${id}`);

export const getFeePayments = (params) => api.get('/fees/payments', { params });
export const createFeePayment = (data) => api.post('/fees/payments', data);
export const deleteFeePayment = (id) => api.delete(`/fees/payments/${id}`);

export const getFeeReport = () => api.get('/fees/report');
