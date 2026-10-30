import axiosClient from "../axios";

export function getCurrentUser({commit}, data) {
  return axiosClient.get('/user', data)
    .then(({data}) => {
      commit('setUser', data);
      return data;
    })
}

export function login({commit}, data) {
  return axiosClient.post('/login', data)
    .then(({data}) => {
      commit('setUser', data.user);
      commit('setToken', data.token)
      return data;
    })
}

export function logout({commit}) {
  return axiosClient.post('/logout')
    .then((response) => {
      commit('setToken', null)

      return response;
    })
}

export function getCountries({commit}) {
  return axiosClient.get('countries')
    .then(({data}) => {
      commit('setCountries', data)
    })
}

export function getOrders({commit, state}, {url = null, search = '', per_page, sort_field, sort_direction} = {}) {
  commit('setOrders', [true])
  url = url || '/orders'
  const params = {
    per_page: state.orders.limit,
  }
  return axiosClient.get(url, {
    params: {
      ...params,
      search, per_page, sort_field, sort_direction
    }
  })
    .then((response) => {
      commit('setOrders', [false, response.data])
    })
    .catch(() => {
      commit('setOrders', [false])
    })
}

export function getOrder({commit}, id) {
  return axiosClient.get(`/orders/${id}`)
}

export function getProducts({commit, state}, {url = null, search = '', per_page, sort_field, sort_direction} = {}) {
  commit('setProducts', [true])
  url = url || '/products'
  const params = {
    per_page: state.products.limit,
  }
  return axiosClient.get(url, {
    params: {
      ...params,
      search, per_page, sort_field, sort_direction
    }
  })
    .then((response) => {
      commit('setProducts', [false, response.data])
    })
    .catch(() => {
      commit('setProducts', [false])
    })
}

export function getProduct({commit}, id) {
  return axiosClient.get(`/products/${id}`)
}

export function createProduct({commit}, product) {
  const form = new FormData();
  form.append('title', product.title);
  (product.images || []).forEach(im => form.append('images[]', im));
  (product.image_order || []).forEach(token => form.append('image_order[]', token));
  if (product.categories) {
    product.categories.forEach(c => form.append('categories[]', c))
  }
  form.append('description', product.description || '');
  form.append('published', product.published ? 1 : 0);
  form.append('price', product.price);
  form.append('quantity', product.quantity ?? '');

  return axiosClient.post('/products', form)
}

export function updateProduct({commit}, product) {
  const id = product.id
  const form = new FormData();
  form.append('id', product.id);
  form.append('title', product.title);
  (product.images || []).forEach(im => form.append('images[]', im));
  (product.image_order || []).forEach(token => form.append('image_order[]', token));
  (product.deleted_images || []).forEach(imageId => form.append('deleted_images[]', imageId));
  if (product.categories) {
    product.categories.forEach(c => form.append('categories[]', c))
  }
  form.append('description', product.description || '');
  form.append('published', product.published ? 1 : 0);
  form.append('price', product.price);
  form.append('quantity', product.quantity ?? '');
  form.append('_method', 'PUT');

  return axiosClient.post(`/products/${id}`, form)
}

export function deleteProduct({commit}, id) {
  return axiosClient.delete(`/products/${id}`)
}

export function getUsers({commit, state}, {url = null, search = '', per_page, sort_field, sort_direction} = {}) {
  commit('setUsers', [true])
  url = url || '/users'
  const params = {
    per_page: state.users.limit,
  }
  return axiosClient.get(url, {
    params: {
      ...params,
      search, per_page, sort_field, sort_direction
    }
  })
    .then((response) => {
      commit('setUsers', [false, response.data])
    })
    .catch(() => {
      commit('setUsers', [false])
    })
}

export function createUser({commit}, user) {
  return axiosClient.post('/users', user)
}

export function updateUser({commit}, user) {
  return axiosClient.put(`/users/${user.id}`, user)
}

export function getCustomers({commit, state}, {url = null, search = '', per_page, sort_field, sort_direction} = {}) {
  commit('setCustomers', [true])
  url = url || '/customers'
  const params = {
    per_page: state.customers.limit,
  }
  return axiosClient.get(url, {
    params: {
      ...params,
      search, per_page, sort_field, sort_direction
    }
  })
    .then((response) => {
      commit('setCustomers', [false, response.data])
    })
    .catch(() => {
      commit('setCustomers', [false])
    })
}

export function getCustomer({commit}, id) {
  return axiosClient.get(`/customers/${id}`)
}

export function createCustomer({commit}, customer) {
  return axiosClient.post('/customers', customer)
}

export function updateCustomer({commit}, customer) {
  return axiosClient.put(`/customers/${customer.id}`, customer)
}

export function deleteCustomer({commit}, customer) {
  return axiosClient.delete(`/customers/${customer.id}`)
}
export function deleteUser({commit}, user) {
  return axiosClient.delete(`/users/${user.id}`)
}

export function getCategories({commit, state}, {sort_field, sort_direction} = {}) {
  commit('setCategories', [true])
  return axiosClient.get('/categories', {
    params: {
      sort_field, sort_direction
    }
  })
    .then((response) => {
      commit('setCategories', [false, response.data])
    })
    .catch(() => {
      commit('setCategories', [false])
    })
}

export function createCategory({commit}, category) {
  return axiosClient.post('/categories', category._form || category)
}

export function updateCategory({commit}, category) {
  if (category._form) {
    category._form.append('_method', 'PUT')
    return axiosClient.post(`/categories/${category.id}`, category._form)
  }
  category._method = 'PUT'
  return axiosClient.post(`/categories/${category.id}`, category)
}

export function deleteCategory({commit}, category) {
  return axiosClient.delete(`/categories/${category.id}`)
}

export function getBanners({commit}) {
  commit('setBanners', [true])
  return axiosClient.get('/banners')
    .then((response) => {
      commit('setBanners', [false, response.data])
    })
    .catch(() => {
      commit('setBanners', [false])
    })
}

export function createBanner({commit}, banner) {
  return axiosClient.post('/banners', banner)
}

export function updateBanner({commit}, banner) {
  if (banner._form) {
    const form = banner._form
    form.append('_method', 'PUT')
    return axiosClient.post(`/banners/${banner.id}`, form)
  }
  banner._method = 'PUT'
  return axiosClient.post(`/banners/${banner.id}`, banner)
}

export function deleteBanner({commit}, banner) {
  return axiosClient.delete(`/banners/${banner.id}`)
}

export function getPaymentMethods({commit}) {
  commit('setPaymentMethods', [true])
  return axiosClient.get('/payment-methods')
    .then((response) => {
      commit('setPaymentMethods', [false, response.data])
    })
    .catch(() => {
      commit('setPaymentMethods', [false])
    })
}

export function createPaymentMethod({commit}, paymentMethod) {
  return axiosClient.post('/payment-methods', paymentMethod)
}

export function updatePaymentMethod({commit}, paymentMethod) {
  return axiosClient.put(`/payment-methods/${paymentMethod.id}`, paymentMethod)
}

export function deletePaymentMethod({commit}, paymentMethod) {
  return axiosClient.delete(`/payment-methods/${paymentMethod.id}`)
}

export function getSettings({commit}) {
  commit('setSettings', [true])
  return axiosClient.get('/settings')
    .then((response) => {
      commit('setSettings', [false, response.data])
    })
    .catch(() => {
      commit('setSettings', [false])
    })
}

export function updateSettings({commit}, settings) {
  return axiosClient.post('/settings', settings)
}
