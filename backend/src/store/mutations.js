
export function setUser(state, user) {
  state.user.data = user;
}

export function setToken(state, token) {
  state.user.token = token;
  if (token) {
    sessionStorage.setItem('TOKEN', token);
  } else {
    sessionStorage.removeItem('TOKEN')
  }
}

export function setProducts(state, [loading, data = null]) {

  if (data) {
    state.products = {
      ...state.products,
      data: data.data,
      links: data.meta?.links,
      page: data.meta.current_page,
      limit: data.meta.per_page,
      from: data.meta.from,
      to: data.meta.to,
      total: data.meta.total,
    }
  }
  state.products.loading = loading;
}

export function setUsers(state, [loading, data = null]) {

  if (data) {
    state.users = {
      ...state.users,
      data: data.data,
      links: data.meta?.links,
      page: data.meta.current_page,
      limit: data.meta.per_page,
      from: data.meta.from,
      to: data.meta.to,
      total: data.meta.total,
    }
  }
  state.users.loading = loading;
}

export function setCustomers(state, [loading, data = null]) {

  if (data) {
    state.customers = {
      ...state.customers,
      data: data.data,
      links: data.meta?.links,
      page: data.meta.current_page,
      limit: data.meta.per_page,
      from: data.meta.from,
      to: data.meta.to,
      total: data.meta.total,
    }
  }
  state.products.loading = loading;
}

export function setOrders(state, [loading, data = null]) {

  if (data) {
    state.orders = {
      ...state.orders,
      data: data.data,
      links: data.meta?.links,
      page: data.meta.current_page,
      limit: data.meta.per_page,
      from: data.meta.from,
      to: data.meta.to,
      total: data.meta.total,
    }
  }
  state.orders.loading = loading;
}

export function showToast(state, message) {
  state.toast.show = true;
  state.toast.message = message;
}

export function hideToast(state) {
  state.toast.show = false;
  state.toast.message = '';
}

export function setCountries(state, countries) {
  state.countries = countries.data;
}

export function setCategories(state, [loading, data = null]) {

  if (data) {
    state.categories = {
      ...state.categories,
      data: data.data,
    }
  }

  state.categories.loading = loading;
}

export function setBanners(state, [loading, data = null]) {

  if (data) {
    state.banners = {
      ...state.banners,
      data: data.data,
    }
  }

  state.banners.loading = loading;
}

export function setPaymentMethods(state, [loading, data = null]) {

  if (data) {
    state.paymentMethods = {
      ...state.paymentMethods,
      data: data.data,
    }
  }

  state.paymentMethods.loading = loading;
}

export function setSettings(state, [loading, data = null]) {

  if (data) {
    state.settings = {
      ...state.settings,
      data: data.data,
    }
  }

  state.settings.loading = loading;
}
